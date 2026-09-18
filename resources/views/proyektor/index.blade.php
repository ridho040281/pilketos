<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Scoreboard & Quick Count - {{ $setting->school_name }}</title>
    <link rel="icon" href="{{ $setting->getFaviconUrl() }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    <script>
        function proyektorApp() {
            return {
                currentTime: '',
                totalVoters: {{ $totalVoters }},
                votedCount: {{ $votedCount }},
                unvotedCount: {{ $unvotedCount }},
                turnoutPercentage: {{ $turnoutPercentage }},
                isFrozen: {{ $setting->show_quick_count ? 'false' : 'true' }},
                candidates: @js($candidates),
                candidateImages: [],
                chartInstance: null,
                lastUpdated: '{{ now()->format('H:i:s') }}',
                chartMetric: 'both', // 'both' | 'votes' | 'percentage'
                selectedCandidateIndex: null, // null | number

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                    this.loadCandidateImages();
                    this.initChart();
                    setInterval(() => this.fetchLiveStats(), 4000);
                },

                loadCandidateImages() {
                    this.candidateImages = this.candidates.map((c) => {
                        const url = c.photo_url || (c.photo_path ? '{{ asset("storage") }}/' + c.photo_path : null);
                        if (!url) return null;
                        const img = new Image();
                        img.crossOrigin = 'anonymous';
                        img.src = url;
                        img.onload = () => {
                            if (this.chartInstance) {
                                this.chartInstance.draw();
                            }
                        };
                        return img;
                    });
                },

                updateClock() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(err => {});
                    } else {
                        document.exitFullscreen().catch(err => {});
                    }
                },

                toggleCandidateSelection(index) {
                    if (this.selectedCandidateIndex === index) {
                        this.selectedCandidateIndex = null;
                    } else {
                        this.selectedCandidateIndex = index;
                    }
                    this.updateChartData(true);
                },

                setChartMetric(metric) {
                    this.chartMetric = metric;
                    this.updateChartData(true);
                },

                getCandidatePercentage(cand) {
                    if (!cand) return '0.0';
                    const total = this.votedCount || 0;
                    const votes = cand.ballots_count ?? cand.votes ?? 0;
                    return total > 0 ? ((votes / total) * 100).toFixed(1) : '0.0';
                },

                updateChartData(animate = false) {
                    if (!this.chartInstance) return;

                    const self = this;
                    const labels = this.candidates.map(c => '{{ $setting->candidate_format_label }} ' + String(c.candidate_number || c.number || 1).padStart(2, '0'));

                    const colors = this.candidates.map((c, i) => {
                        const baseColor = c.color_tag || c.card_color || '#4f46e5';
                        if (self.selectedCandidateIndex !== null && self.selectedCandidateIndex !== i) {
                            return baseColor + '35'; // dimmed when another candidate is clicked
                        }
                        return baseColor;
                    });

                    let datasetData = [];
                    if (this.chartMetric === 'percentage') {
                        datasetData = this.candidates.map(c => parseFloat(self.getCandidatePercentage(c)));
                    } else {
                        datasetData = this.candidates.map(c => c.ballots_count ?? c.votes ?? 0);
                    }

                    this.chartInstance.data.labels = labels;
                    this.chartInstance.data.datasets[0].data = datasetData;
                    this.chartInstance.data.datasets[0].backgroundColor = colors;
                    this.chartInstance.update(animate ? 'default' : 'none');
                },

                initChart() {
                    const ctx = document.getElementById('quickCountChart');
                    if (!ctx) return;

                    const self = this;
                    const labels = this.candidates.map(c => '{{ $setting->candidate_format_label }} ' + String(c.candidate_number || c.number || 1).padStart(2, '0'));
                    const colors = this.candidates.map(c => c.color_tag || c.card_color || '#4f46e5');
                    const data = this.candidates.map(c => c.ballots_count ?? c.votes ?? 0);

                    // Helper to draw rounded rect across all canvas engines
                    function drawRoundedRect(ctx, x, y, width, height, radius) {
                        ctx.beginPath();
                        ctx.moveTo(x + radius, y);
                        ctx.lineTo(x + width - radius, y);
                        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
                        ctx.lineTo(x + width, y + height - radius);
                        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                        ctx.lineTo(x + radius, y + height);
                        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
                        ctx.lineTo(x, y + radius);
                        ctx.quadraticCurveTo(x, y, x + radius, y);
                        ctx.closePath();
                    }

                    const candidateTopAvatarPlugin = {
                        id: 'candidateTopAvatarPlugin',
                        afterDatasetsDraw(chart) {
                            const ctx = chart.ctx;
                            const meta = chart.getDatasetMeta(0);
                            if (!meta || !meta.data) return;

                            meta.data.forEach((bar, index) => {
                                const cand = (self.candidates && self.candidates[index]) ? self.candidates[index] : null;
                                if (!cand) return;

                                const isSelected = (self.selectedCandidateIndex === index);
                                const x = bar.x;
                                const y = bar.y;
                                const radius = isSelected ? 37 : 33;
                                const centerY = Math.max(chart.chartArea.top + radius + 34, y - 4);
                                const candColor = cand.color_tag || cand.card_color || '#4f46e5';

                                ctx.save();

                                // 1. Glowing accent background circle
                                ctx.beginPath();
                                ctx.arc(x, centerY, radius + 3.5, 0, Math.PI * 2);
                                ctx.fillStyle = candColor;
                                ctx.shadowColor = isSelected ? candColor : 'rgba(0, 0, 0, 0.55)';
                                ctx.shadowBlur = isSelected ? 22 : 12;
                                ctx.shadowOffsetY = 4;
                                ctx.fill();

                                // 2. White border ring base
                                ctx.beginPath();
                                ctx.arc(x, centerY, radius + 2, 0, Math.PI * 2);
                                ctx.fillStyle = '#ffffff';
                                ctx.fill();

                                // 3. Clip circular avatar area
                                ctx.save();
                                ctx.beginPath();
                                ctx.arc(x, centerY, radius, 0, Math.PI * 2);
                                ctx.closePath();
                                ctx.clip();

                                const img = self.candidateImages ? self.candidateImages[index] : null;
                                if (img && img.complete && img.naturalWidth !== 0) {
                                    const aspect = img.naturalWidth / img.naturalHeight;
                                    let sx = 0, sy = 0, sWidth = img.naturalWidth, sHeight = img.naturalHeight;
                                    if (aspect > 1) {
                                        sWidth = img.naturalHeight;
                                        sx = (img.naturalWidth - sWidth) / 2;
                                    } else {
                                        sHeight = img.naturalWidth;
                                        sy = (img.naturalHeight - sHeight) * 0.15;
                                    }
                                    ctx.drawImage(img, sx, sy, sWidth, sHeight, x - radius, centerY - radius, radius * 2, radius * 2);
                                } else {
                                    ctx.fillStyle = '#0f172a';
                                    ctx.fillRect(x - radius, centerY - radius, radius * 2, radius * 2);
                                    ctx.fillStyle = '#ffffff';
                                    ctx.font = 'bold 18px "Plus Jakarta Sans", sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(String(cand.candidate_number || cand.number || (index + 1)).padStart(2, '0'), x, centerY);
                                }
                                ctx.restore();

                                // 4. Crisp outer white border
                                ctx.beginPath();
                                ctx.arc(x, centerY, radius, 0, Math.PI * 2);
                                ctx.lineWidth = isSelected ? 4 : 3;
                                ctx.strokeStyle = '#ffffff';
                                ctx.stroke();

                                // 5. Candidate number mini-badge pill at bottom right
                                const badgeRadius = 11;
                                const badgeX = x + radius - 6;
                                const badgeY = centerY + radius - 6;
                                ctx.beginPath();
                                ctx.arc(badgeX, badgeY, badgeRadius, 0, Math.PI * 2);
                                ctx.fillStyle = candColor;
                                ctx.fill();
                                ctx.lineWidth = 2;
                                ctx.strokeStyle = '#ffffff';
                                ctx.stroke();

                                ctx.fillStyle = '#ffffff';
                                ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(String(cand.candidate_number || cand.number || (index + 1)), badgeX, badgeY);

                                // 6. Floating percentage / votes badge (Always cleanly rendered)
                                const votes = cand.ballots_count ?? cand.votes ?? 0;
                                const pct = self.getCandidatePercentage(cand);

                                let badgeText = '';
                                if (self.chartMetric === 'percentage') {
                                    badgeText = isSelected ? `${votes.toLocaleString('id-ID')} Suara (${pct}%)` : `${pct}%`;
                                } else if (self.chartMetric === 'votes') {
                                    badgeText = isSelected ? `${votes.toLocaleString('id-ID')} Suara (${pct}%)` : `${votes.toLocaleString('id-ID')} Suara`;
                                } else {
                                    // 'both' mode (default)
                                    badgeText = `${votes.toLocaleString('id-ID')} Suara (${pct}%)`;
                                }

                                ctx.save();
                                ctx.font = isSelected ? 'bold 12.5px "Plus Jakarta Sans", sans-serif' : 'bold 11.5px "Plus Jakarta Sans", sans-serif';
                                const textWidth = ctx.measureText(badgeText).width;
                                const pillPaddingX = 10;
                                const pillWidth = textWidth + pillPaddingX * 2;
                                const pillHeight = 26;
                                const pillY = centerY - radius - pillHeight - 6;
                                const pillX = x - pillWidth / 2;

                                // Shadow
                                ctx.shadowColor = isSelected ? candColor : 'rgba(0, 0, 0, 0.75)';
                                ctx.shadowBlur = isSelected ? 18 : 10;
                                ctx.shadowOffsetY = 4;

                                // Pill Background
                                drawRoundedRect(ctx, pillX, pillY, pillWidth, pillHeight, 13);
                                ctx.fillStyle = isSelected ? '#020617' : '#0f172a';
                                ctx.fill();

                                // Pill Border
                                ctx.lineWidth = isSelected ? 2.5 : 1.5;
                                ctx.strokeStyle = isSelected ? '#ffffff' : candColor;
                                ctx.stroke();

                                // Downward Arrow Pointer
                                ctx.beginPath();
                                ctx.moveTo(x - 5, pillY + pillHeight);
                                ctx.lineTo(x + 5, pillY + pillHeight);
                                ctx.lineTo(x, pillY + pillHeight + 5);
                                ctx.closePath();
                                ctx.fillStyle = isSelected ? '#020617' : '#0f172a';
                                ctx.fill();
                                ctx.lineWidth = isSelected ? 2.5 : 1.5;
                                ctx.strokeStyle = isSelected ? '#ffffff' : candColor;
                                ctx.stroke();

                                // Text
                                ctx.shadowColor = 'transparent';
                                ctx.fillStyle = '#ffffff';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(badgeText, x, pillY + pillHeight / 2);

                                ctx.restore();

                                ctx.restore();
                            });
                        }
                    };

                    this.chartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Perolehan Suara',
                                data: data,
                                backgroundColor: colors,
                                borderRadius: 12,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            onHover: (event, chartElement) => {
                                const target = event.native ? event.native.target : (event.chart ? event.chart.canvas : null);
                                if (target) {
                                    target.style.cursor = chartElement && chartElement.length ? 'pointer' : 'default';
                                }
                            },
                            onClick: (event, elements) => {
                                if (elements && elements.length > 0) {
                                    const index = elements[0].index;
                                    self.toggleCandidateSelection(index);
                                } else {
                                    self.selectedCandidateIndex = null;
                                    self.updateChartData(true);
                                }
                            },
                            layout: {
                                padding: {
                                    top: 68,
                                    left: 15,
                                    right: 15,
                                    bottom: 0
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false } // Native tooltip disabled so black box never gets stuck!
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grace: '28%',
                                    ticks: {
                                        color: '#94a3b8',
                                        font: { family: 'Plus Jakarta Sans', weight: 'bold' },
                                        callback: function(val) {
                                            return self.chartMetric === 'percentage' ? val + '%' : val;
                                        }
                                    },
                                    grid: { color: '#1e293b' }
                                },
                                x: {
                                    ticks: { color: '#f8fafc', font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 14 } },
                                    grid: { display: false }
                                }
                            }
                        },
                        plugins: [candidateTopAvatarPlugin]
                    });
                },

                async fetchLiveStats() {
                    try {
                        const res = await fetch('{{ route('proyektor.api') }}');
                        if (!res.ok) return;
                        const data = await res.json();
                        
                        this.totalVoters = data.total_voters;
                        this.votedCount = data.voted_count;
                        this.unvotedCount = data.unvoted_count;
                        this.turnoutPercentage = data.turnout_percentage;
                        this.isFrozen = data.is_frozen;
                        this.lastUpdated = data.updated_at;

                        // Update chart if not frozen
                        if (!this.isFrozen && this.chartInstance && data.candidates) {
                            this.candidates = data.candidates;
                            this.updateChartData(false);
                        }
                    } catch (err) {
                        console.error('Polling error:', err);
                    }
                }
            };
        }
    </script>
</head>
<body class="h-full flex flex-col justify-between overflow-x-hidden p-4 sm:p-6 lg:p-8 selection:bg-indigo-500 selection:text-white"
    x-data="proyektorApp()"
>
    <!-- Top Header -->
    <header class="flex flex-col md:flex-row items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div class="flex items-center space-x-4 text-center md:text-left">
            @if ($setting->getSchoolLogoUrl())
                <img src="{{ $setting->getSchoolLogoUrl() }}" alt="Logo {{ $setting->school_name }}" class="w-12 h-12 object-contain rounded-2xl bg-white p-1 shadow-lg shadow-indigo-950/40">
            @else
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center font-black text-2xl text-white shadow-lg shadow-indigo-500/30">
                    P
                </div>
            @endif
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-400 block">{{ $setting->school_name }}</span>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">{{ $setting->election_title }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Digital Clock -->
            <div class="px-4 py-2 rounded-2xl bg-slate-900 border border-slate-800 text-center font-mono">
                <span class="text-xs text-slate-400 block">WAKTU SERVER</span>
                <span class="text-base sm:text-lg font-bold text-indigo-300" x-text="currentTime">--:--:-- WIB</span>
            </div>

            <!-- Fullscreen Button -->
            <button @click="toggleFullscreen()" class="p-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors" title="Layar Penuh">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
            </button>
        </div>
    </header>

    <!-- Main Live Stats KPIs -->
    <section class="my-6 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <!-- Total DPT -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 sm:p-6 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total DPT Terdaftar</span>
                <span class="p-2 rounded-xl bg-slate-800 text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl sm:text-5xl font-black text-white" x-text="totalVoters.toLocaleString('id-ID')">{{ number_format($totalVoters, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-400 font-semibold">Siswa/Pemilih</span>
            </div>
        </div>

        <!-- Suara Masuk -->
        <div class="bg-slate-900/90 border border-indigo-900/50 rounded-3xl p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-indigo-950/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Suara Sah Masuk</span>
                <span class="p-2 rounded-xl bg-indigo-950/80 text-indigo-400 border border-indigo-800/40">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl sm:text-5xl font-black text-indigo-400" x-text="votedCount.toLocaleString('id-ID')">{{ number_format($votedCount, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-400 font-semibold">Surat Suara</span>
            </div>
        </div>

        <!-- Partisipasi -->
        <div class="bg-slate-900/90 border border-emerald-900/50 rounded-3xl p-5 sm:p-6 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Tingkat Partisipasi</span>
                <span class="p-2 rounded-xl bg-emerald-950/80 text-emerald-400 border border-emerald-800/40">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl sm:text-5xl font-black text-emerald-400" x-text="turnoutPercentage + '%'">{{ $turnoutPercentage }}%</span>
                <span class="text-xs text-slate-400 font-semibold" x-text="'(' + unvotedCount + ' belum)'"></span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-slate-800 rounded-full h-2 mt-4 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2 rounded-full transition-all duration-500" :style="'width: ' + turnoutPercentage + '%'"></div>
            </div>
        </div>
    </section>

    <!-- Scoreboard Body: Frozen Mode vs Live Chart -->
    <main class="flex-1 flex flex-col justify-center">
        <!-- IF FROZEN MODE IS ACTIVE -->
        <div x-show="isFrozen" class="bg-slate-900/60 border-2 border-dashed border-slate-800 rounded-3xl p-8 sm:p-12 text-center max-w-4xl w-full mx-auto my-auto" x-cloak>
            <div class="w-20 h-20 mx-auto mb-5 rounded-3xl bg-amber-950/60 border border-amber-800/60 text-amber-400 flex items-center justify-center shadow-lg shadow-amber-950/50">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <span class="inline-block text-xs font-extrabold uppercase tracking-widest text-amber-400 mb-2 px-3.5 py-1.5 rounded-full bg-amber-950/80 border border-amber-800/40">
                Freeze Mode &bull; Perolehan Suara Dirahasiakan
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">Pemungutan Suara Sedang Berlangsung</h2>
            <p class="text-sm text-slate-400 max-w-2xl mx-auto mt-3 leading-relaxed">
                Rincian perolehan suara {{ strtolower($setting->candidate_format_label) }} disembunyikan sementara selama proses pemungutan suara berlangsung demi menjaga netralitas dan asas LUBER. Grafik perolehan resmi akan dibuka serentak pada saat <strong>Sidang Pleno Penghitungan Suara</strong> oleh Panitia.
            </p>

            <!-- Grid of candidates in silhouette / neutral mode -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
                @foreach ($candidates as $c)
                    <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex items-center space-x-3">
                        <span class="w-10 h-10 rounded-xl bg-slate-800 text-slate-300 font-black text-lg flex items-center justify-center shrink-0">
                            {{ sprintf('%02d', $c->candidate_number) }}
                        </span>
                        <div class="text-left min-w-0">
                            <div class="text-xs font-bold text-slate-200 truncate">{{ $c->leader_name }}</div>
                            @if(!empty($c->co_leader_name))
                                <div class="text-[11px] text-slate-500 truncate">& {{ $c->co_leader_name }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- IF LIVE QUICK COUNT IS ACTIVE (UNFROZEN) -->
        <div x-show="!isFrozen" class="grid grid-cols-1 lg:grid-cols-3 gap-6 my-auto" x-cloak>
            <!-- Chart Column (2 cols on large) -->
            <div class="lg:col-span-2 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></span>
                            Grafik Perolehan Suara {{ $setting->candidate_format_label }}
                        </h3>
                        <span class="text-xs text-slate-400 font-mono hidden md:inline">Diperbarui: <span x-text="lastUpdated"></span></span>
                    </div>

                    <!-- Toggle Metrik: Suara | Persen (%) | Suara + % (Gabungan Opsi 1 & 2) -->
                    <div class="flex items-center p-1 bg-slate-950/80 border border-slate-800 rounded-xl gap-1 text-xs font-semibold self-start sm:self-auto">
                        <button 
                            type="button"
                            @click="setChartMetric('votes')"
                            :class="chartMetric === 'votes' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                            class="px-2.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5"
                            title="Tampilkan Jumlah Suara"
                        >
                            <span>📊</span>
                            <span>Suara</span>
                        </button>
                        <button 
                            type="button"
                            @click="setChartMetric('percentage')"
                            :class="chartMetric === 'percentage' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                            class="px-2.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5"
                            title="Tampilkan Skala & Nilai Persentase"
                        >
                            <span>📈</span>
                            <span>Persen (%)</span>
                        </button>
                        <button 
                            type="button"
                            @click="setChartMetric('both')"
                            :class="chartMetric === 'both' ? 'bg-gradient-to-r from-indigo-600 to-emerald-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                            class="px-2.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5"
                            title="Tampilkan Suara Sekaligus Persentase"
                        >
                            <span>✨</span>
                            <span>Semua</span>
                        </button>
                    </div>
                </div>
                <div class="h-64 sm:h-80 w-full relative">
                    <canvas id="quickCountChart"></canvas>
                </div>
            </div>

            <!-- Candidate Cards Column -->
            <div class="space-y-4 flex flex-col justify-center">
                @foreach ($candidates as $index => $candidate)
                    <div 
                        @click="toggleCandidateSelection({{ $index }})"
                        :class="{
                            'ring-2 ring-indigo-400 shadow-lg shadow-indigo-950/60 bg-slate-800/90 scale-[1.02]': selectedCandidateIndex === {{ $index }},
                            'bg-slate-900/80 hover:bg-slate-900 border-slate-800 hover:border-slate-700': selectedCandidateIndex !== {{ $index }}
                        }"
                        class="border rounded-3xl p-5 cursor-pointer transition-all duration-200 select-none relative overflow-hidden group"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <span 
                                    style="background-color: {{ $candidate->card_color }};"
                                    class="w-10 h-10 rounded-2xl text-white font-black text-lg flex items-center justify-center shadow-md shrink-0"
                                >
                                    {{ sprintf('%02d', $candidate->candidate_number) }}
                                </span>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-white leading-tight truncate group-hover:text-indigo-300 transition-colors">{{ $candidate->leader_name }}</h4>
                                    @if(!empty($candidate->co_leader_name))
                                        <p class="text-xs text-slate-400 truncate">& {{ $candidate->co_leader_name }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Live Percentage Pill Badge -->
                            <div 
                                class="px-2.5 py-1 rounded-full text-xs font-black flex items-center gap-1 border shrink-0 transition-all"
                                :class="selectedCandidateIndex === {{ $index }} ? 'bg-indigo-500/20 text-indigo-300 border-indigo-400/40' : 'bg-slate-800/90 text-slate-300 border-slate-700/60'"
                            >
                                <span x-text="getCandidatePercentage(candidates[{{ $index }}]) + '%'">
                                    {{ $votedCount > 0 ? round(($candidate->ballots_count / $votedCount) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>

                        <!-- Progress and count -->
                        <div class="flex items-baseline justify-between pt-2 border-t border-slate-800/80">
                            <span class="text-xs text-slate-400 font-medium">Perolehan</span>
                            <div class="text-right">
                                <span class="text-xl font-extrabold text-white" id="count-{{ $candidate->id }}" x-text="(candidates[{{ $index }}]?.ballots_count ?? candidates[{{ $index }}]?.votes ?? {{ $candidate->ballots_count }}).toLocaleString('id-ID')">{{ $candidate->ballots_count }}</span>
                                <span class="text-xs text-slate-400">suara</span>
                            </div>
                        </div>

                        <!-- Mini progress bar -->
                        <div class="w-full bg-slate-800/80 rounded-full h-1.5 mt-2.5 overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all duration-500" 
                                style="background-color: {{ $candidate->card_color }};"
                                :style="'width: ' + getCandidatePercentage(candidates[{{ $index }}]) + '%'"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </main>

    <!-- Footer Status -->
    <footer class="mt-6 pt-4 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Live Stream Sinkronisasi Otomatis Setiap 4 Detik</span>
        </div>
        <div>
            Pilketos OSIS &bull; Asas LUBER JURDIL
        </div>
    </footer>
</body>
</html>
