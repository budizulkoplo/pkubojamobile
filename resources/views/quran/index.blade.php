@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">QUR'AN</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
{{-- Google Font untuk Arab --}}
<link href="https://fonts.googleapis.com/css2?family=Scheherazade+New&display=swap" rel="stylesheet">

<style>
    .arab-title {
        font-family: 'Scheherazade New', serif;
        font-size: 1.6rem;  /* diperbesar */
        text-align: right;
        direction: rtl;
    }
</style>

<div class="p-3" style="margin-top: 40px">
    <h2 class="mb-3">Daftar Surat</h2>

    {{-- Filter pencarian --}}
    <div class="mb-3">
        <input type="text" id="searchSurat" class="form-control" placeholder="Cari surat...">
    </div>

    <div class="list-group" id="suratList">
        @foreach($surat as $s)
            <a href="{{ route('quran.show', $s['nomor']) }}" class="list-group-item list-group-item-action surat-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $s['namaLatin'] }}</strong> ({{ $s['jumlahAyat'] }} ayat)<br>
                        <small>{{ $s['arti'] }}</small>
                    </div>
                    <div class="arab-title">
                        {{ $s['nama'] }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>

{{-- Script filter pencarian --}}
<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("searchSurat");
    const items = document.querySelectorAll(".surat-item");

    input.addEventListener("keyup", function () {
        const filter = input.value.toLowerCase();
        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            if (text.includes(filter)) {
                item.style.display = "block";
            } else {
                item.style.display = "none";
            }
        });
    });
});
</script>
@endsection
