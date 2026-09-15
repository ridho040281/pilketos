<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ballot;
use App\Models\ElectionSetting;
use App\Models\Voter;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            $query->where('class', $class);
        }

        if ($status = $request->input('status')) {
            if ($status === 'voted') {
                $query->where('has_voted', true);
            } elseif ($status === 'unvoted') {
                $query->where('has_voted', false);
            }
        }

        $voters = $query->orderBy('category')->orderBy('class')->orderBy('name')->paginate(25)->withQueryString();
        $classes = Voter::select('class')->distinct()->orderBy('class')->pluck('class');
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
            'setting',
            'totalCount',
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
            'nisn' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', 'in:L,P'],
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
            'nisn' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', 'in:L,P'],
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
     * Download sample CSV template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_dpt_pilketos.csv"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Kategori (siswa/guru/tendik)', 'NISN/NIP', 'Nama Lengkap', 'Kelas/Unit Kerja', 'Jenis Kelamin (L/P)']);
            fputcsv($handle, ['siswa', '20260001', 'Ahmad Fauzi', 'X-1', 'L']);
            fputcsv($handle, ['siswa', '20260002', 'Budi Santoso', 'X-1', 'L']);
            fputcsv($handle, ['guru', '197505122005011002', 'Drs. Hendro Wibowo, M.Pd.', 'Guru Matematika', 'L']);
            fputcsv($handle, ['tendik', '198804152019032008', 'Sri Wahyuni, S.Kom.', 'Staf Tata Usaha', 'P']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import DPT from CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Check BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $rowIndex = 0;
            $delimiter = ',';

            // Detect delimiter (comma vs semicolon)
            $firstLine = fgets($handle);
            rewind($handle);
            if (str_contains($firstLine, ';')) {
                $delimiter = ';';
            }

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $rowIndex++;
                // Skip header
                if ($rowIndex === 1) {
                    continue;
                }

                // If 5+ columns: Kategori, NISN/NIP, Nama, Kelas/Unit, JK
                // If 4 columns: NISN, Nama, Kelas, JK (default kategori: siswa)
                if (count($data) >= 5) {
                    $rawCategory = strtolower(trim((string) $data[0]));
                    $nisn = ! empty($data[1]) ? trim((string) $data[1]) : null;
                    $name = ! empty($data[2]) ? trim((string) $data[2]) : '';
                    $class = ! empty($data[3]) ? trim((string) $data[3]) : 'Umum';
                    $gender = ! empty($data[4]) ? strtoupper(trim((string) $data[4])) : null;
                } else {
                    $rawCategory = 'siswa';
                    $nisn = ! empty($data[0]) ? trim((string) $data[0]) : null;
                    $name = ! empty($data[1]) ? trim((string) $data[1]) : '';
                    $class = ! empty($data[2]) ? trim((string) $data[2]) : 'Umum';
                    $gender = ! empty($data[3]) ? strtoupper(trim((string) $data[3])) : null;
                }

                if (empty($name)) {
                    continue;
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
            fclose($handle);
        }

        if (empty($rows)) {
            return back()->with('error', 'File CSV kosong atau format kolom tidak sesuai.');
        }

        // Insert in chunks of 500 for optimal speed
        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                Voter::insert($chunk);
            }
        });

        return redirect()->route('admin.voters.index')
            ->with('success', 'Berhasil mengimpor '.count($rows).' data pemilih (DPT) beserta generate token unik.');
    }

    /**
     * Printable voter cards formatted for A4 cutting.
     */
    public function printCards(Request $request): View
    {
        $query = Voter::query();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($class = $request->input('class')) {
            $query->where('class', $class);
        }

        $voters = $query->orderBy('category')->orderBy('class')->orderBy('name')->get();
        $setting = ElectionSetting::current();
        $categories = Voter::CATEGORIES;
        $classes = Voter::select('class')->distinct()->orderBy('class')->pluck('class');

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

        return view('admin.voters.print-cards', compact('voterCards', 'setting', 'class', 'category', 'categories', 'classes'));
    }

    /**
     * Truncate/Reset all votes (dangerous action).
     */
    public function resetVotes(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_password' => ['required', 'string'],
        ]);

        if (! auth()->validate(['username' => auth()->user()->username, 'password' => $request->confirm_password])) {
            return back()->with('error', 'Konfirmasi password salah. Reset suara dibatalkan.');
        }

        DB::transaction(function (): void {
            Ballot::query()->delete();
            Voter::query()->update([
                'has_voted' => false,
                'voted_at' => null,
            ]);
        });

        return redirect()->route('admin.dashboard')->with('success', 'Seluruh kotak suara dan status hak pilih berhasil di-reset ke awal.');
    }
}
