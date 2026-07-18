@extends('layouts.presensi')

@section('title', 'ID Card Pegawai')

@section('content')
<div class="idcard-page">
    <div class="idcard-shell">
        <div class="idcard-header">
            <div>
                <div class="eyebrow">HRIS Mobile</div>
                <h1>ID Card Pegawai</h1>
                <p>Atur foto dan posisi kartu secara mandiri, lalu simpan hasilnya.</p>
            </div>
            <a href="/dashboard" class="back-link">Kembali</a>
        </div>

        <div class="grid">
            <section class="preview-panel">
                <div class="preview-frame">
                    <div id="cardPreview" class="card-preview">
                        <img id="savedOverlay" class="saved-overlay {{ $savedCardUrl ? '' : 'hidden' }}" src="{{ $savedCardUrl ?: '' }}" alt="ID card tersimpan">
                        <img id="cardPhoto" class="card-photo" src="{{ $photoUrl }}" alt="Foto pegawai">
                        <div id="photoPlaceholder" class="photo-placeholder {{ $photoUrl ? 'hidden' : '' }}">Foto</div>
                        <div class="identity-bar">
                            <div id="cardName">{{ $pegawai->pegawai_nama ?: '-' }}</div>
                            <div id="cardPart">{{ $pegawai->bagian ?: '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="saved-note {{ $savedCardUrl ? '' : 'hidden' }}" id="savedNote">
                    ID card terakhir sudah tersimpan.
                    <a id="savedLink" href="{{ $savedCardUrl ?: '#' }}" target="_blank">Lihat hasil</a>
                </div>
            </section>

            <section class="editor-panel">
                <div class="field">
                    <label>Nama</label>
                    <input type="text" value="{{ $pegawai->pegawai_nama ?: '-' }}" readonly>
                </div>
                <div class="field">
                    <label>Bagian</label>
                    <input type="text" value="{{ $pegawai->bagian ?: '-' }}" readonly>
                </div>

                <div class="field">
                    <label>Upload Foto</label>
                    <input type="file" id="photoUpload" accept="image/*">
                </div>

                <div class="adjust-box">
                    <div class="adjust-title">Atur Foto</div>
                    <div class="field">
                        <label>Geser Horizontal</label>
                        <input type="range" id="offsetX" min="-120" max="120" value="24">
                    </div>
                    <div class="field">
                        <label>Geser Vertikal</label>
                        <input type="range" id="offsetY" min="-100" max="100" value="0">
                    </div>
                    <div class="field">
                        <label>Zoom</label>
                        <input type="range" id="scale" min="80" max="150" value="100">
                    </div>
                    <button type="button" id="resetAdjust" class="secondary">Reset Posisi</button>
                </div>

                <div class="actions">
                    <button type="button" id="saveCard" class="primary">Simpan ID Card</button>
                    <button type="button" id="downloadCard" class="secondary">Download PNG</button>
                </div>

                <div class="status" id="statusText"></div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
(() => {
    const card = document.getElementById('cardPreview');
    const photo = document.getElementById('cardPhoto');
    const placeholder = document.getElementById('photoPlaceholder');
    const upload = document.getElementById('photoUpload');
    const offsetX = document.getElementById('offsetX');
    const offsetY = document.getElementById('offsetY');
    const scale = document.getElementById('scale');
    const resetAdjust = document.getElementById('resetAdjust');
    const saveCard = document.getElementById('saveCard');
    const downloadCard = document.getElementById('downloadCard');
    const statusText = document.getElementById('statusText');
    const savedOverlay = document.getElementById('savedOverlay');
    const savedNote = document.getElementById('savedNote');
    const savedLink = document.getElementById('savedLink');
    const saveUrl = @json(route('hris.idcard.save'));
    const currentSavedUrl = @json($savedCardUrl);
    const currentPhotoUrl = @json($photoUrl);
    let selectedFile = null;

    function setStatus(message, tone = '') {
        statusText.textContent = message || '';
        statusText.className = tone ? `status ${tone}` : 'status';
    }

    function applyAdjust() {
        const translateX = -198 + Number(offsetX.value);
        const translateY = Number(offsetY.value);
        const zoom = Number(scale.value) / 100;
        const transform = `translate(${translateX}px, ${translateY}px) scale(${zoom})`;
        photo.style.transform = transform;
        placeholder.style.transform = transform;
    }

    function resetValues() {
        offsetX.value = 24;
        offsetY.value = 0;
        scale.value = 100;
        applyAdjust();
        setStatus('');
    }

    function loadSelectedPhoto(file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            photo.src = event.target.result;
            photo.classList.remove('hidden');
            placeholder.classList.add('hidden');
            savedOverlay?.classList.add('hidden');
            selectedFile = file;
            setStatus('Foto baru siap diatur.');
        };
        reader.readAsDataURL(file);
    }

    async function canvasCapture() {
        const output = document.createElement('canvas');
        output.width = 396;
        output.height = 612;
        const ctx = output.getContext('2d');

        const drawBackground = () => {
            const gradient = ctx.createLinearGradient(0, 0, 0, output.height);
            gradient.addColorStop(0, '#f4fbff');
            gradient.addColorStop(1, '#eef7f7');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, output.width, output.height);

            ctx.fillStyle = 'rgba(11, 143, 137, 0.12)';
            ctx.beginPath();
            ctx.arc(-20, -10, 150, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = 'rgba(255, 159, 67, 0.12)';
            ctx.beginPath();
            ctx.arc(420, 0, 120, 0, Math.PI * 2);
            ctx.fill();
        };

        const loadImage = (src) => new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('Gambar gagal dimuat.'));
            img.src = src;
        });

        const drawPhoto = async () => {
            if (!photo.src || photo.classList.contains('hidden')) {
                return;
            }

            const img = await loadImage(photo.src);
            const zoom = Number(scale.value) / 100;
            const width = img.width * zoom;
            const height = img.height * zoom;
            const x = (output.width - width) / 2 + Number(offsetX.value);
            const y = 74 + Number(offsetY.value);

            ctx.drawImage(img, x, y, width, height);
        };

        drawBackground();

        if (photo.src && !photo.classList.contains('hidden')) {
            await drawPhoto();
        } else {
            ctx.fillStyle = 'rgba(11, 143, 137, 0.12)';
            ctx.fillRect(8, 74, output.width - 16, 512);
            ctx.strokeStyle = 'rgba(255,255,255,0.8)';
            ctx.lineWidth = 2;
            ctx.setLineDash([8, 6]);
            ctx.strokeRect(18, 84, output.width - 36, 492);
            ctx.setLineDash([]);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 18px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Foto', output.width / 2, 330);
        }

        ctx.fillStyle = 'rgba(181, 95, 42, 0.84)';
        ctx.fillRect(8, 592 - 96, output.width - 16, 80);
        ctx.fillStyle = '#fff';
        ctx.textAlign = 'left';
        ctx.font = '900 22px sans-serif';
        wrapText(ctx, String(document.getElementById('cardName').textContent || '-').toUpperCase(), 16, 518, 360, 24);
        ctx.font = '800 18px sans-serif';
        wrapText(ctx, String(document.getElementById('cardPart').textContent || '-').toUpperCase(), 16, 550, 360, 20);

        return output.toDataURL('image/png');
    }

    function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
        const words = text.split(/\s+/);
        let line = '';
        let cursorY = y;

        for (const word of words) {
            const testLine = line ? `${line} ${word}` : word;
            const metrics = ctx.measureText(testLine);

            if (metrics.width > maxWidth && line) {
                ctx.fillText(line, x, cursorY);
                line = word;
                cursorY += lineHeight;
            } else {
                line = testLine;
            }
        }

        if (line) {
            ctx.fillText(line, x, cursorY);
        }
    }

    offsetX.addEventListener('input', applyAdjust);
    offsetY.addEventListener('input', applyAdjust);
    scale.addEventListener('input', applyAdjust);

    resetAdjust.addEventListener('click', resetValues);

    upload.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        if (!file) return;
        loadSelectedPhoto(file);
    });

    saveCard.addEventListener('click', async () => {
        try {
            setStatus('Menyimpan ID card...', 'loading');
            const image = await canvasCapture();
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({ image })
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'ID card gagal disimpan.');
            }

            if (savedOverlay) {
                savedOverlay.src = payload.url;
                savedOverlay.classList.remove('hidden');
            }
            if (savedNote) {
                savedNote.classList.remove('hidden');
            }
            if (savedLink) {
                savedLink.href = payload.url;
            }
            setStatus(payload.message || 'ID card berhasil disimpan.', 'success');
        } catch (error) {
            setStatus(error.message || 'Gagal menyimpan ID card.', 'error');
        }
    });

    downloadCard.addEventListener('click', async () => {
        try {
            setStatus('Menyiapkan file PNG...', 'loading');
            const image = await canvasCapture();
            const link = document.createElement('a');
            link.href = image;
            link.download = 'ID-Card-{{ \Illuminate\Support\Str::slug((string) $pegawai->pegawai_nama, '-') ?: 'pegawai' }}.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setStatus('PNG berhasil disiapkan.', 'success');
        } catch (error) {
            setStatus(error.message || 'Gagal membuat PNG.', 'error');
        }
    });

    if (currentPhotoUrl) {
        applyAdjust();
    } else {
        placeholder.classList.remove('hidden');
    }

    if (!currentSavedUrl && savedOverlay) {
        savedOverlay.classList.add('hidden');
    }
})();
</script>
<style>
    .idcard-page {
        min-height: 100vh;
        padding: 14px;
        background:
            radial-gradient(circle at top left, rgba(7, 184, 178, .18), transparent 34%),
            radial-gradient(circle at top right, rgba(255, 159, 67, .12), transparent 28%),
            linear-gradient(180deg, #f6fbff 0%, #eef7f7 100%);
    }

    .idcard-shell {
        max-width: 1100px;
        margin: 0 auto;
    }

    .idcard-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 14px;
        padding: 16px 18px;
        border-radius: 20px;
        background: rgba(255,255,255,.86);
        box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
    }

    .eyebrow {
        color: #0b8f89;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .idcard-header h1 {
        margin: 4px 0 6px;
        font-size: 28px;
        line-height: 1;
    }

    .idcard-header p {
        margin: 0;
        color: #63738a;
        font-size: 13px;
        font-weight: 600;
    }

    .back-link,
    .primary,
    .secondary {
        border: 0;
        border-radius: 14px;
        padding: 11px 16px;
        font: inherit;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
    }

    .back-link,
    .secondary {
        color: #16334f;
        background: #edf3f7;
    }

    .primary {
        color: #fff;
        background: linear-gradient(135deg, #0b8f89, #21a86b);
    }

    .grid {
        display: grid;
        grid-template-columns: 1.05fr .95fr;
        gap: 14px;
    }

    .preview-panel,
    .editor-panel {
        padding: 16px;
        border-radius: 20px;
        background: rgba(255,255,255,.9);
        box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
    }

    .preview-frame {
        display: flex;
        justify-content: center;
        padding: 12px;
        border-radius: 18px;
        background: #f4f7fb;
        border: 1px solid rgba(15, 23, 42, .06);
    }

    .card-preview {
        position: relative;
        width: 396px;
        height: 612px;
        overflow: hidden;
        border-radius: 18px;
        background:
            linear-gradient(180deg, rgba(255,255,255,.94), rgba(255,255,255,.72)),
            linear-gradient(135deg, rgba(11,143,137,.18), rgba(33,168,107,.12));
        box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
    }

    .card-preview::before {
        position: absolute;
        inset: 0;
        content: "";
        background-image:
            linear-gradient(rgba(255,255,255,.12) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.12) 1px, transparent 1px);
        background-size: 42px 42px;
        opacity: .35;
        pointer-events: none;
    }

    .card-photo,
    .photo-placeholder,
    .saved-overlay {
        position: absolute;
        top: 74px;
        left: 50%;
        width: 396px;
        height: 512px;
        transform: translate(-172px, 0) scale(1);
        transform-origin: center bottom;
    }

    .card-photo {
        object-fit: contain;
        object-position: center bottom;
        z-index: 1;
    }

    .photo-placeholder {
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,.92);
        font-size: 18px;
        font-weight: 800;
        border: 2px dashed rgba(255,255,255,.75);
        border-radius: 12px;
        background: rgba(11, 143, 137, .12);
    }

    .saved-overlay {
        z-index: 5;
        object-fit: contain;
        background: #fff;
    }

    .identity-bar {
        position: absolute;
        z-index: 6;
        right: 8px;
        left: 8px;
        bottom: 15px;
        min-height: 80px;
        padding: 10px 12px;
        border-radius: 12px;
        background: rgba(181, 95, 42, .84);
        color: #fff;
    }

    .identity-bar #cardName,
    .identity-bar #cardPart {
        word-break: break-word;
        text-transform: uppercase;
        text-shadow: -1px -1px 0 #263238, 1px -1px 0 #263238, -1px 1px 0 #263238, 1px 1px 0 #263238;
    }

    .identity-bar #cardName {
        font-size: 22px;
        font-weight: 900;
        line-height: 1.05;
    }

    .identity-bar #cardPart {
        margin-top: 2px;
        font-size: 18px;
        font-weight: 800;
    }

    .field {
        margin-bottom: 12px;
    }

    .field label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 800;
        color: #344b64;
    }

    .field input[type="text"],
    .field input[type="file"] {
        width: 100%;
    }

    .field input[type="text"] {
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid #d7e0ea;
        border-radius: 12px;
        background: #f8fbfd;
        color: #10233f;
    }

    .field input[type="range"] {
        width: 100%;
    }

    .adjust-box {
        margin-top: 14px;
        padding: 14px;
        border-radius: 16px;
        background: #f7fafc;
        border: 1px solid #e6edf4;
    }

    .adjust-title {
        margin-bottom: 10px;
        color: #0b8f89;
        font-size: 14px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
    }

    .status {
        min-height: 22px;
        margin-top: 12px;
        color: #63738a;
        font-size: 13px;
        font-weight: 700;
    }

    .status.success { color: #127a4b; }
    .status.error { color: #a94442; }
    .status.loading { color: #0b8f89; }

    .saved-note {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        background: #eaf8f1;
        color: #126b43;
        font-size: 13px;
        font-weight: 700;
    }

    .saved-note a {
        color: inherit;
        font-weight: 900;
    }

    .hidden {
        display: none !important;
    }

    @media (max-width: 980px) {
        .grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .idcard-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .card-preview {
            width: 100%;
            max-width: 396px;
            aspect-ratio: 396 / 612;
            height: auto;
        }

        .card-photo,
        .photo-placeholder,
        .saved-overlay {
            width: 100%;
            height: 100%;
        }

        .actions .primary,
        .actions .secondary,
        .back-link {
            width: 100%;
            text-align: center;
        }
    }
</style>
@endsection
