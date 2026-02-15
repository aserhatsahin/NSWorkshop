# Neriman Şahin Resim Atölyesi Yönetim Sistemi

Bu proje, bir resim atölyesinin öğrenci takibini, ödeme yönetimini ve ürün satışlarını kolaylaştırmak için geliştirilmiş bir web uygulamasıdır.

## Özellikler

- **Öğrenci Yönetimi**: Yeni öğrenci kaydı, profil düzenleme, aktif/pasif durumu.
- **Finansal Takip**: Aylık kurs ücretleri, toplam borç takibi ve ödeme alma.
- **Ürün Satışı**: Atölye içi ürün satışı ve stok takibi (basit düzeyde).
- **Dinamik Arama**: Öğrencileri isme göre anlık arama.
- **Otomatik Borçlandırma**: Her ayın başında öğrencilere otomatik kurs ücreti yansıtma.

## Kurulum (MAMP ile)

1.  Bu projeyi `/Applications/MAMP/htdocs/NSWorkshop` dizinine kopyalayın.
2.  MAMP sunucusunu başlatın (Apache: 8888, MySQL: 8889).
3.  `resim_atolyesi_backup.sql` dosyasını `resim_atolyesi` veritabanına içe aktarın.
4.  Tarayıcınızda `http://localhost:8888/NSWorkshop/` adresine gidin.

## Son Güncellemeler (v2.0 - Art Workshop Theme)

- **Yeni Arayüz**: "Art Workshop" teması ile açık renkli, ferah ve galeri tarzı bir tasarım.
- **Performans İyileştirmesi**: `apply_monthly_fees.php` scripti optimize edilerek timeout sorunları giderildi.
- **Veritabanı Düzeltmesi**: `total_debt` alanı boyutu artırılarak büyük tutarlar için destek sağlandı.
- **Navigasyon**: Tüm sayfalara sabit navigasyon barı eklendi.

## Teknoloji Yığını

- **Backend**: PHP 8.x (PDO ile)
- **Veritabanı**: MySQL 8.0
- **Frontend**: HTML5, CSS3 (Custom Properties, Flexbox), JavaScript (Fetch API)

## Lisans

Bu proje Neriman Şahin Resim Atölyesi için özel olarak geliştirilmiştir.