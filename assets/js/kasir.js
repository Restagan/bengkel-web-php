(function () {
  'use strict';

  var form = document.getElementById('formKasir');
  if (!form) return;

  var daftar = document.getElementById('kasirBaris');
  var templat = document.getElementById('templatBaris');
  var tombolTambah = document.getElementById('tambahBaris');
  var totalEl = document.getElementById('kasirTotal');
  var bayarEl = document.getElementById('uangDiterima');
  var kembaliEl = document.getElementById('kasirKembali');
  var simpan = document.getElementById('tombolSimpan');
  var uangPas = document.getElementById('uangPas');
  var teksSimpan = simpan.textContent;
  var MAKS_BARIS = 50;
  var MAKS_JUMLAH = 9999;
  var totalTerakhir = 0;

  function rupiah(n) {
    return 'Rp ' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function bacaUang(teks) {
    var d = String(teks || '').replace(/,\d{0,2}$/, '').replace(/\D/g, '');
    return d === '' ? null : parseInt(d, 10);
  }

  function semuaBaris() {
    return Array.prototype.slice.call(daftar.querySelectorAll('[data-baris]'));
  }

  function sinkronkanPilihan() {
    var dipakai = {};
    semuaBaris().forEach(function (b) {
      var v = b.querySelector('[data-pilih]').value;
      if (v) dipakai[v] = true;
    });
    semuaBaris().forEach(function (b) {
      var pilih = b.querySelector('[data-pilih]');
      Array.prototype.forEach.call(pilih.options, function (o) {
        if (!o.value) return;
        var habis = (parseInt(o.getAttribute('data-stok'), 10) || 0) <= 0;
        var dipakaiBarisLain = dipakai[o.value] && pilih.value !== o.value;
        o.disabled = habis || !!dipakaiBarisLain;
      });
    });
  }

  function hitung() {
    sinkronkanPilihan();
    var total = 0;
    var valid = 0;
    var salah = false;

    semuaBaris().forEach(function (b) {
      var pilih = b.querySelector('[data-pilih]');
      var jumlahEl = b.querySelector('[data-jumlah]');
      var info = b.querySelector('[data-info]');
      var sub = b.querySelector('[data-sub]');
      var opsi = pilih.options[pilih.selectedIndex];

      jumlahEl.classList.remove('is-invalid');
      info.className = 'kasir-info';

      if (!pilih.value) {
        info.textContent = 'Pilih sparepart untuk melihat harga dan stok.';
        sub.textContent = rupiah(0);
        jumlahEl.max = MAKS_JUMLAH;
        return;
      }

      var harga = parseInt(opsi.getAttribute('data-harga'), 10) || 0;
      var stok = parseInt(opsi.getAttribute('data-stok'), 10) || 0;
      var satuan = opsi.getAttribute('data-satuan') || '';
      var mentah = jumlahEl.value.trim();
      var jumlah = parseInt(mentah, 10);
      var bulat = /^\d+$/.test(mentah);

      jumlahEl.max = Math.max(1, Math.min(stok, MAKS_JUMLAH));

      if (!bulat || jumlah < 1 || jumlah > MAKS_JUMLAH) {
        salah = true;
        jumlahEl.classList.add('is-invalid');
        info.className = 'kasir-info kasir-info-salah';
        info.textContent = 'Jumlah harus bilangan bulat 1 sampai ' + MAKS_JUMLAH + '.';
        sub.textContent = rupiah(0);
        return;
      }
      if (jumlah > stok) {
        salah = true;
        jumlahEl.classList.add('is-invalid');
        info.className = 'kasir-info kasir-info-salah';
        info.textContent = 'Stok hanya ' + stok + ' ' + satuan + '.';
        sub.textContent = rupiah(0);
        return;
      }

      info.textContent = rupiah(harga) + ' / ' + satuan + ' - stok ' + stok;
      var subtotal = harga * jumlah;
      sub.textContent = rupiah(subtotal);
      total += subtotal;
      valid++;
    });

    totalTerakhir = total;
    totalEl.textContent = rupiah(total);

    var bayar = bacaUang(bayarEl.value);
    var kurangBayar = false;
    if (bayar === null) {
      kembaliEl.className = 'kasir-kembali';
      kembaliEl.textContent = valid > 0 ? 'Uang diterima kosong, dianggap uang pas.' : '';
    } else if (bayar >= total) {
      kembaliEl.className = 'kasir-kembali kasir-kembali-ok';
      kembaliEl.textContent = 'Kembalian ' + rupiah(bayar - total);
    } else {
      kurangBayar = true;
      kembaliEl.className = 'kasir-kembali kasir-kembali-kurang';
      kembaliEl.textContent = 'Kurang ' + rupiah(total - bayar);
    }

    simpan.disabled = !(valid > 0 && !salah && !kurangBayar);
  }

  function tambahBaris() {
    if (semuaBaris().length >= MAKS_BARIS) return;
    var baru = templat.content.querySelector('[data-baris]').cloneNode(true);
    daftar.appendChild(baru);
    hitung();
    baru.querySelector('[data-pilih]').focus();
  }

  function hapusBaris(baris) {
    if (semuaBaris().length <= 1) {
      baris.querySelector('[data-pilih]').value = '';
      baris.querySelector('[data-jumlah]').value = '1';
    } else {
      baris.parentNode.removeChild(baris);
    }
    hitung();
  }

  daftar.addEventListener('input', hitung);
  daftar.addEventListener('change', hitung);
  daftar.addEventListener('click', function (e) {
    var tombol = e.target.closest ? e.target.closest('[data-hapus]') : null;
    if (tombol) hapusBaris(tombol.closest('[data-baris]'));
  });

  tombolTambah.addEventListener('click', tambahBaris);
  bayarEl.addEventListener('input', hitung);

  uangPas.addEventListener('click', function () {
    bayarEl.value = String(totalTerakhir);
    bayarEl.dispatchEvent(new Event('input', { bubbles: true }));
    hitung();
  });

  form.addEventListener('submit', function (e) {
    hitung();
    if (simpan.disabled) {
      e.preventDefault();
      return;
    }
    simpan.disabled = true;
    simpan.textContent = 'Menyimpan...';
  });

  window.addEventListener('pageshow', function () {
    simpan.textContent = teksSimpan;
    hitung();
  });

  hitung();
})();
