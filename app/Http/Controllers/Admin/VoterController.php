<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ballot;
use App\Models\ElectionSetting;
use App\Models\Voter;
use App\Support\SimpleXLSX;
use App\Support\SimpleXLSXGen;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoterController extends Controller
{
    /**
     * Display DPT list with search and filters.
     */
    public function index(Request $request): View
    {
        $query = Voter::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('passcode', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($class = $request->input('class')) {
            if ($class === '[Tanpa Kelas]') {
                $query->where(function ($q): void {
                    $q->whereNull('class')->orWhereRaw("TRIM(class) = ''");
                });
            } else {
                $query->where('class', $class);
            }
        }

        if ($status = $request->input('status')) {
            if ($status === 'voted') {
                $query->where('has_voted', true);
            } elseif ($status === 'unvoted') {
                $query->where('has_voted', false);
            }
        }

        $voters = $query->orderBy('category')->orderBy('class')->orderBy('name')->paginate(25)->withQueryString();
        $filteredCount = $voters->total();
        $isFiltered = $request->filled('search') || $request->filled('category') || $request->filled('class') || $request->filled('status');
        $classes = Voter::whereNotNull('class')->where('class', '!=', '')->distinct()->orderBy('class')->pluck('class');

        $categoryClasses = [
            Voter::CATEGORY_SISWA => [],
            Voter::CATEGORY_GURU => [],
            Voter::CATEGORY_TENDIK => [],
        ];
        foreach (Voter::whereNotNull('class')->where('class', '!=', '')->select('category', 'class')->distinct()->orderBy('class')->get() as $item) {
            if (isset($categoryClasses[$item->category])) {
                $categoryClasses[$item->category][] = $item->class;
            } else {
                $categoryClasses[$item->category] = [$item->class];
            }
        }

        $classesCounts = Voter::whereNotNull('class')
            ->where('class', '!=', '')
            ->selectRaw('class, count(*) as count')
            ->groupBy('class')
            ->pluck('count', 'class');

        $emptyClassCount = Voter::where(function ($q): void {
            $q->whereNull('class')->orWhereRaw("TRIM(class) = ''");
        })->count();

        if ($emptyClassCount > 0) {
            $classes->push('[Tanpa Kelas]');
            $categoryClasses[Voter::CATEGORY_SISWA][] = '[Tanpa Kelas]';
            $classesCounts['[Tanpa Kelas]'] = $emptyClassCount;
        }

        $setting = ElectionSetting::current();

        $totalCount = Voter::count();
        $votedCount = Voter::where('has_voted', true)->count();
        $unvotedCount = max(0, $totalCount - $votedCount);

        $siswaCount = Voter::where('category', Voter::CATEGORY_SISWA)->count();
        $guruCount = Voter::where('category', Voter::CATEGORY_GURU)->count();
        $tendikCount = Voter::where('category', Voter::CATEGORY_TENDIK)->count();
        $categories = Voter::CATEGORIES;

        return view('admin.voters.index', compact(
            'voters',
            'classes',
            'classesCounts',
            'categoryClasses',
            'setting',
            'totalCount',
            'filteredCount',
            'isFiltered',
            'votedCount',
            'unvotedCount',
            'siswaCount',
            'guruCount',
            'tendikCount',
            'categories'
        ));
    }

    /**
     * Show form for creating a new voter.
     */
    public function create(): View
    {
        $categories = Voter::CATEGORIES;

        return view('admin.voters.create', compact('categories'));
    }

    /**
     * Store newly created voter.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:siswa,guru,tendik'],
            'nisn' => ['nullable', 'string', 'max:30', 'unique:voters,nisn'],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', 'in:L,P'],
        ], [
            'nisn.unique' => 'NISN/NIP ini sudah terdaftar dalam DPT.',
        ]);

        $validated['passcode'] = Voter::generatePasscode();
        $validated['has_voted'] = false;

        Voter::create($validated);

        return redirect()->route('admin.voters.index')->with('success', 'Pemilih berhasil ditambahkan.');
    }

    /**
     * Show edit form for voter.
     */
    public function edit(Voter $voter): View
    {
        $categories = Voter::CATEGORIES;

        return view('admin.voters.edit', compact('voter', 'categories'));
    }

    /**
     * Update voter details.
     */
    public function update(Request $request, Voter $voter): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:siswa,guru,tendik'],
            'nisn' => ['nullable', 'string', 'max:30', Rule::unique('voters', 'nisn')->ignore($voter->id)],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', 'in:L,P'],
        ], [
            'nisn.unique' => 'NISN/NIP ini sudah terdaftar pada pemilih lain dalam DPT.',
        ]);

        $voter->update($validated);

        return redirect()->route('admin.voters.index')->with('success', 'Data pemilih berhasil diperbarui.');
    }

    /**
     * Delete voter.
     */
    public function destroy(Voter $voter): RedirectResponse
    {
        $voter->delete();

        return redirect()->route('admin.voters.index')->with('success', 'Data pemilih berhasil dihapus.');
    }

    /**
     * Download sample Excel template (.xlsx).
     */
    public function downloadTemplate(): Response
    {
        $data = [
            ['Kategori (siswa/guru/tendik)', 'NISN/NIP', 'Nama Lengkap', 'Kelas/Unit Kerja', 'Jenis Kelamin (L/P)'],
            ['siswa', '20260001', 'Ahmad Fauzi', 'X-1', 'L'],
            ['siswa', '20260002', 'Budi Santoso', 'X-1', 'L'],
            ['guru', '197505122005011002', 'Drs. Hendro Wibowo, M.Pd.', 'Guru Matematika', 'L'],
            ['tendik', '198804152019032008', 'Sri Wahyuni, S.Kom.', 'Staf Tata Usaha', 'P'],
        ];

        $xlsx = SimpleXLSXGen::fromArray($data, 'Template DPT');

        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template_dpt_pilketos.xlsx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Import DPT from Excel or CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'csv', 'txt'])) {
            return back()->with('error', 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).');
        }

        $path = $file->getRealPath();
        $rawRows = $this->parseFileRows($path, $extension);

        if (empty($rawRows)) {
            return back()->with('error', 'File Excel/CSV kosong atau data tidak dapat dibaca.');
        }

        $existingNisns = Voter::whereNotNull('nisn')
            ->where('nisn', '!=', '')
            ->pluck('nisn')
            ->map(fn ($n) => trim((string) $n))
            ->flip()
            ->toArray();

        $seenInFileNisns = [];
        $duplicateCount = 0;
        $rows = [];
        $headerSkipped = false;

        foreach ($rawRows as $data) {
            $rowValues = array_map(fn ($cell) => trim((string) $cell), (array) $data);

            // Skip empty rows
            if (empty(array_filter($rowValues, fn ($v) => $v !== ''))) {
                continue;
            }

            // Skip header row if found
            if (! $headerSkipped) {
                $headerSkipped = true;
                $firstCell = strtolower($rowValues[0] ?? '');
                if (in_array($firstCell, ['kategori', 'kategori (siswa/guru/tendik)', 'nisn', 'nisn/nip', 'no', 'nomor'])) {
                    continue;
                }
            }

            // If 5+ columns: Kategori, NISN/NIP, Nama, Kelas/Unit, JK
            // If 4 columns: NISN, Nama, Kelas, JK (default kategori: siswa)
            if (count($rowValues) >= 5) {
                $rawCategory = strtolower($rowValues[0] ?? '');
                $nisn = ! empty($rowValues[1]) ? trim((string) $rowValues[1]) : null;
                $name = ! empty($rowValues[2]) ? trim((string) $rowValues[2]) : '';
                $class = ! empty($rowValues[3]) ? trim((string) $rowValues[3]) : 'Umum';
                $gender = ! empty($rowValues[4]) ? strtoupper(trim((string) $rowValues[4])) : null;
            } else {
                $rawCategory = 'siswa';
                $nisn = ! empty($rowValues[0]) ? trim((string) $rowValues[0]) : null;
                $name = ! empty($rowValues[1]) ? trim((string) $rowValues[1]) : '';
                $class = ! empty($rowValues[2]) ? trim((string) $rowValues[2]) : 'Umum';
                $gender = ! empty($rowValues[3]) ? strtoupper(trim((string) $rowValues[3])) : null;
            }

            if (empty($name)) {
                continue;
            }

            // Validasi: Jika NIP/NISN sama, tidak bisa diimpor lagi
            if ($nisn !== null && $nisn !== '') {
                if (isset($existingNisns[$nisn]) || isset($seenInFileNisns[$nisn])) {
                    $duplicateCount++;

                    continue;
                }
                $seenInFileNisns[$nisn] = true;
            }

            if (in_array($rawCategory, ['guru', 'pendidik', 'teacher'])) {
                $category = Voter::CATEGORY_GURU;
            } elseif (in_array($rawCategory, ['tendik', 'tenaga kependidikan', 'staff', 'tu', 'karyawan'])) {
                $category = Voter::CATEGORY_TENDIK;
            } else {
                $category = Voter::CATEGORY_SISWA;
            }

            if (! in_array($gender, ['L', 'P'])) {
                $gender = null;
            }

            $rows[] = [
                'category' => $category,
                'nisn' => $nisn,
                'name' => $name,
                'class' => $class,
                'gender' => $gender,
                'passcode' => Voter::generatePasscode(),
                'has_voted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($rows)) {
            if ($duplicateCount > 0) {
                return back()->with('error', "Tidak ada data baru yang diimpor. Sebanyak {$duplicateCount} baris data dilewati karena NISN/NIP sudah terdaftar di DPT atau dobel dalam file.");
            }

            return back()->with('error', 'Tidak ada data pemilih yang valid ditemukan dalam file.');
        }

        // Insert in chunks of 500 for optimal speed
        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                Voter::insert($chunk);
            }
        });

        $message = 'Berhasil mengimpor '.count($rows).' data pemilih baru (DPT) beserta generate token unik.';
        if ($duplicateCount > 0) {
            $message .= " Sebanyak {$duplicateCount} data dilewati karena NISN/NIP sudah terdaftar sebelumnya.";
        }

        return redirect()->route('admin.voters.index')->with('success', $message);
    }

    /**
     * Parse rows from Excel or CSV file.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function parseFileRows(string $path, string $extension): array
    {
        $rawRows = [];

        if (in_array($extension, ['xlsx', 'xls'])) {
            $xlsx = SimpleXLSX::parse($path);
            if ($xlsx) {
                $rawRows = $xlsx->rows();
            }
        }

        // If not parsed by SimpleXLSX (or if extension is csv/txt), fallback to CSV reader
        if (empty($rawRows) && ($handle = fopen($path, 'r')) !== false) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = str_contains((string) $firstLine, ';') ? ';' : ',';

            while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
                $rawRows[] = $data;
            }
            fclose($handle);
        }

        return $rawRows;
    }

    /**
     * Printable voter cards formatted for A4 cutting.
     */
    public function printCards(Request $request): View
    {
        $query = Voter::query();

        $category = $request->input('category');
        $class = $request->input('class');

        $classesQuery = Voter::whereNotNull('class')->where('class', '!=', '');
        if ($category) {
            $classesQuery->where('category', $category);
        }
        $classes = $classesQuery->distinct()->orderBy('class')->pluck('class');

        if ($category && $class && ! $classes->contains($class)) {
            $class = null;
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($class) {
            $query->where('class', $class);
        }

        $voters = $query->orderBy('category')->orderBy('class')->orderBy('name')->get();
        $setting = ElectionSetting::current();
        $categories = Voter::CATEGORIES;

        // QR Code options (chillerlan/php-qrcode v6)
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'addQuietzone' => false,
        ]);
        $qrCode = new QRCode($options);

        // Generate QR Code data URI for each voter
        $voterCards = $voters->map(function ($voter) use ($qrCode) {
            $votingUrl = url('/?token='.$voter->passcode);
            try {
                $voter->qr_svg = $qrCode->render($votingUrl);
            } catch (\Throwable $th) {
                $voter->qr_svg = null;
            }

            return $voter;
        });

        $layout = in_array((int) $request->input('layout', 20), [8, 10, 20], true) ? (int) $request->input('layout', 20) : 20;
        $showQr = $request->has('show_qr') ? $request->boolean('show_qr') : (bool) ($setting->show_qr_code ?? true);

        return view('admin.voters.print-cards', compact('voterCards', 'setting', 'class', 'category', 'categories', 'classes', 'layout', 'showQr'));
    }

    /**
     * Truncate/Reset all votes (dangerous action).
     */
    public function resetVotes(Request $request): RedirectResponse
    {
        $password = $request->input('confirm_password', '');
        $confirmation = strtoupper(trim((string) $request->input('confirmation', '')));

        $isValid = false;
        if (! empty($password) && auth()->validate(['username' => auth()->user()->username, 'password' => $password])) {
            $isValid = true;
        } elseif ($confirmation === 'RESET') {
            $isValid = true;
        }

        if (! $isValid) {
            return back()->with('error', 'Konfirmasi keamanan salah. Masukkan password admin atau ketik RESET.');
        }

        DB::transaction(function (): void {
            Ballot::query()->delete();
            Voter::query()->update([
                'has_voted' => false,
                'voted_at' => null,
            ]);
        });

        return back()->with('success', 'Seluruh kotak suara dan status hak pilih berhasil di-reset ke awal.');
    }
}
