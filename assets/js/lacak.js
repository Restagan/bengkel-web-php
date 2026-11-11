/*
 * Live tracking: memeriksa perubahan status di server secara berkala.
 * Jika status berubah, halaman dimuat ulang otomatis. Jika tidak, hanya jam "terakhir diperiksa" yang diperbarui.
 * Dipakai oleh lacak.php. Server hanya mengirim "tanda perubahan" (gabungan status), bukan data pelanggan.
 */
(function () {
  'use strict';

  var kotak = document.getElementById('lacakHasil');
  if (!kotak || !kotak.getAttribute('data-poll')) return; // semua servis sudah selesai: tidak perlu memeriksa

  var selang = (parseInt(kotak.getAttribute('data-selang'), 10) || 30) * 1000;
  var tanda = kotak.getAttribute('data-tanda');
  var alamat = kotak.getAttribute('data-json');
  var jam = document.getElementById('lacakJam');
  var sedangMemeriksa = false;

  function dua(n) { return (n < 10 ? '0' : '') + n; }

  function periksa() {
    if (sedangMemeriksa) return;
    sedangMemeriksa = true;
    fetch(alamat, { cache: 'no-store', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.tanda && data.tanda !== tanda) {
          window.location.reload();
          return;
        }
        var d = new Date();
        if (jam) jam.textContent = dua(d.getHours()) + ':' + dua(d.getMinutes()) + ':' + dua(d.getSeconds());
      })
      .catch(function () { /* koneksi terputus sebentar: coba lagi pada putaran berikutnya */ })
      .then(function () { sedangMemeriksa = false; });
  }

  setInterval(periksa, selang);

  // Kembali ke tab ini setelah lama ditinggal: langsung periksa
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) periksa();
  });
})();
