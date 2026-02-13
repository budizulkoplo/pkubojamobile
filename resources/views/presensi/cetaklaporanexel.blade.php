<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>A4</title>

  <!-- Normalize or reset CSS with your favorite library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">

  <!-- Load paper.css for happy printing -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">

  <!-- Set page size here: A5, A4 or A3 -->
  <!-- Set also "landscape" if you need -->
  <style>
    @page {
            size: A4 
                }

                #title {
                        font-size: 18px;
                        font-weight: bold;
                }

                .tabeldatakaryawan{
                    margin-top 40px;
                }

                .tabeldatakaryawan{
                    padding 5px;
                }

                .tabelpresensi{
                    width: 100%;
                    margin-top: 20px;
                    border-collapse: collapse;
                }
                                                                                                                                                                                                    
                .tabelpresensi tr th{
                    border: 1px solid #131212;
                    padding: 8px;
                    background: #dbdbdb;
                }

                .tabelpresensi tr td{
                    border: 1px solid #131212;
                    padding: 5px;
                    font-size: 12px;
                }

                .foto{
                    width: 40px;
                    height: 30px;
                }

         </style>
</head>

<!-- Set "A5", "A4" or "A3" for class name -->
<!-- Set also "landscape" if you need -->
<body class="A4">

  <!-- Each sheet element should have the class "sheet" -->
  <!-- "padding-**mm" is optional: you can set 10, 15, 20 or 25 -->
  <section class="sheet padding-10mm">

   <table style="width: 100%">
        <tr>
            <td style="width: 10%">
                <img src="{{ asset('assets/img/logopku.jpeg') }}" width="80" height="100" alt="">
            </td>
            <td>
                <span id="title">
                    LAPORAN PRESENSI AHAD<br>
                    PERIODE {{ strtoupper($namabulan[$bulan]) }} {{ $tahun }}<br>
                    RS PKU MUHAMMADIYAH BOJA
                </span><br>
                <span><i>Jl.Raya Boja Limbangan Ds.Salamsari Kec.Boja Kab.Kendal</i></span>
            </tb>
        </tr>
   </table>
   <table class="tabeldatakaryawan">
        <tr>
        <td rowspan="6">
        @php
            $path = Storage::url('uploads/karyawan/'.$karyawan->foto);
        @endphp
        <img src="{{ url($path) }}" alt="" width="120px" height="150">
        </td>
        </tr>
        <tr>
            <td>NIK</td>
            <td>:</td>
            <td>{{ $karyawan->nik }}</td>
        </tr>
        <tr>
            <td>Nama Karyawan</td>
            <td>:</td>
            <td>{{ $karyawan->nama_lengkap }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $karyawan->jabatan }}</td>
        </tr>
        <tr>
            <td>Departemen</td>
            <td>:</td>
            <td>{{ $karyawan->kode_dept }}</td>
        </tr>
        <tr>
            <td>No.HP</td>
            <td>:</td>
            <td>{{ $karyawan->no_hp }}</td>
        </tr>
   </table>
   <table class="tabelpresensi">
    <tr>
        <th>No.</th>
        <th>Tanggal</th>
        <th>Jam Absen</th>
    </tr>
    @foreach ($presensi as $d)
    @php

    @endphp
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ date("d-m-Y",strtotime($d->tgl_presensi)) }}</td>
        <td>{{ $d->jam_in }}</td>
    </tr>
    @endforeach
   </table>

   <table width="100%" style="margin-top:100px">
    <tr>
        <td colspan="2" style="text-align: right">Kendal, {{ date('d-m-Y') }}</td>
    </tr>
    <tr>
        <td style="text-align: center; vertical-align:bottom" height="100px">
            <u>Deni Kurniawan, S.sos</u><br>
            <i><b>Pembina Rohani</b></i>
        </td>
        <td style="text-align: center; vertical-align:bottom">
            <u>dr. Arfa Bima Firizqina, MARS</u><br>
            <i><b>Direktur</b></i>
        </td>
    </tr>
  </section>
</body>
</html>