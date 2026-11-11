/*
 * Skrip kecil untuk semua halaman:
 *  1. Titik ribuan otomatis pada input bertanda data-rupiah
 *  2. Mengisi form di modal Edit dari atribut data-* pada tombol yang diklik
 *  3. Batas jumlah suku cadang mengikuti stok yang dipilih
 *  4. Dialog konfirmasi untuk form bertanda data-konfirmasi
 */
(function () {
  'use strict';

  var MAKS_DIGIT = 13; // sama dengan batas kolom DECIMAL(15,2)

  // ---------- 1. Titik ribuan otomatis ----------
  function formatRupiah(el) {
    var nilai = el.value;
    var posisi = el.selectionStart == null ? nilai.length : el.selectionStart;
    var digitSebelumKursor = nilai.slice(0, posisi).replace(/\D/g, '').length;

    // Sen di belakang koma ("2.500.000,00") dibuang, rupiah tidak memakai desimal
    var digit = nilai.replace(/,\d{0,2}$/, '').replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, MAKS_DIGIT);
    var hasil = digit.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    if (hasil === nilai) return;
    el.value = hasil;

    // Kembalikan kursor ke tempat semula (titik yang ikut bertambah tidak menggeser kursor)
    if (document.activeElement === el && el.setSelectionRange) {
      var baru = hasil.length;
      if (digitSebelumKursor === 0) {
        baru = 0;
      } else {
        var hitung = 0;
        for (var i = 0; i < hasil.length; i++) {
          if (/\d/.test(hasil.charAt(i))) hitung++;
          if (hitung === digitSebelumKursor) { baru = i + 1; break; }
        }
      }
      el.setSelectionRange(baru, baru);
    }
  }

  function formatSemua(akar) {
    var daftar = (akar || document).querySelectorAll('input[data-rupiah]');
    for (var i = 0; i < daftar.length; i++) formatRupiah(daftar[i]);
  }

  document.addEventListener('input', function (e) {
    if (e.target.matches && e.target.matches('input[data-rupiah]')) formatRupiah(e.target);
  });
  formatSemua();

  // ---------- 2. Isi otomatis modal Edit ----------
  // Tombol: <button data-bs-toggle="modal" data-bs-target="#modalEdit" data-nama="Budi" data-telp="08...">
  // Modal : <div class="modal" id="modalEdit" data-isi-form> ... <input name="nama"> ...
  // Setiap data-xxx pada tombol diisikan ke field form yang bernama "xxx".
  document.addEventListener('show.bs.modal', function (e) {
    var tombol = e.relatedTarget;
    var modal = e.target;
    if (!tombol || !modal.hasAttribute('data-isi-form')) return;
    var form = modal.querySelector('form');
    if (!form) return;

    Object.keys(tombol.dataset).forEach(function (kunci) {
      if (kunci.indexOf('bs') === 0) return; // data-bs-toggle, data-bs-target
      var field = form.elements[kunci];
      if (field && field.value !== undefined) field.value = tombol.dataset[kunci];
    });
    formatSemua(modal);
  });

  // Fokus ke isian pertama yang bisa diketik saat modal terbuka
  document.addEventListener('shown.bs.modal', function (e) {
    var pertama = e.target.querySelector('input:not([type=hidden]):not([readonly]):not([disabled]), textarea');
    if (pertama) pertama.focus();
  });

  // ---------- 3. Batas jumlah mengikuti stok ----------
  // <select data-batas-jumlah="#isiJumlah"> dengan <option data-stok="12">: input jumlah dibatasi sebesar stok.
  document.addEventListener('change', function (e) {
    var pilih = e.target;
    if (!pilih.matches || !pilih.matches('select[data-batas-jumlah]')) return;
    var isian = document.querySelector(pilih.getAttribute('data-batas-jumlah'));
    var opsi = pilih.options[pilih.selectedIndex];
    if (!isian || !opsi) return;
    var stok = parseInt(opsi.getAttribute('data-stok'), 10);
    if (stok > 0) {
      isian.max = stok;
      if (parseInt(isian.value, 10) > stok) isian.value = stok;
    }
  });

  // ---------- 4. Konfirmasi ----------
  document.addEventListener('submit', function (e) {
    var pesan = e.target.getAttribute && e.target.getAttribute('data-konfirmasi');
    if (pesan && !window.confirm(pesan)) e.preventDefault();
  });
})();
