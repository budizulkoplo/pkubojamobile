@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left"><a href="{{ url('/dashboard') }}" class="headerButton"><ion-icon name="chevron-back-outline"></ion-icon></a></div>
    <div class="pageTitle">Aset</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:70px">
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    <div class="card p-3">
        <h4 class="mb-2">Scan Barcode Aset</h4>
        <p class="text-muted">Arahkan kamera ke barcode aset.</p>
        <button type="button" id="start-camera" class="btn btn-primary w-100 mb-3">Aktifkan Kamera</button>
        <select id="camera-select" class="form-control mb-3" hidden></select>
        <div id="reader" class="mb-3"></div>
        <form method="GET" action="{{ route('aset.search') }}">
            <label for="code">Masukkan kode manual</label>
            <div class="input-group">
                <input id="code" name="code" class="form-control" placeholder="2025/03/PKUBOJA/EM9/002" required>
                <button class="btn btn-primary" type="submit"><ion-icon name="search-outline"></ion-icon></button>
            </div>
        </form>
    </div>
</div>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
const reader = new Html5Qrcode('reader');
const cameraSelect = document.getElementById('camera-select');
let scanning = false;
function startCamera(id) {
    reader.start(id, { fps: 10, qrbox: { width: 250, height: 250 } }, (text) => {
        if (scanning) return;
        scanning = true;
        window.location.href = '{{ route('aset.search') }}?code=' + encodeURIComponent(text);
    }, () => {}).catch(() => alert('Kamera tidak dapat diaktifkan. Pastikan izin kamera dan HTTPS tersedia.'));
}
document.getElementById('start-camera').addEventListener('click', () => {
    Html5Qrcode.getCameras().then((cameras) => {
        if (!cameras.length) return alert('Kamera tidak ditemukan.');
        cameraSelect.innerHTML = cameras.map((camera, index) => `<option value="${camera.id}">${camera.label || 'Kamera ' + (index + 1)}</option>`).join('');
        cameraSelect.hidden = false;
        const preferred = cameras.find((camera) => /back|environment/i.test(camera.label)) || cameras[0];
        cameraSelect.value = preferred.id;
        startCamera(preferred.id);
    }).catch(() => alert('Izin kamera ditolak atau kamera tidak tersedia.'));
});
cameraSelect.addEventListener('change', () => reader.stop().then(() => startCamera(cameraSelect.value)));
</script>
@endsection
