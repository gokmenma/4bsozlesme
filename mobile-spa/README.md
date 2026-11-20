# Sözleşme 4B Mobile SPA (Vue 3 + PWA)

`mobile.4bsozlesme.com` alan adı altında çalışan Vue 3 tabanlı mobil PWA uygulamasıdır.

## Geliştirme

```bash
cd /opt/lampp/htdocs/sozlesme-4b/mobile-spa
npm install
npm run dev
```

## Üretim Build

```bash
npm run build
```

Apache sanal sunucu (vhost) belge kökü `/opt/lampp/htdocs/sozlesme-4b/mobile-spa/dist` klasörünü gösterir.

## Yerel XAMPP Vhost + Hosts Kurulumu

```bash
sudo bash /opt/lampp/htdocs/sozlesme-4b/mobile-spa/bin/install-local-vhost.sh
```

Betik `/opt/lampp/etc/extra/httpd-vhosts.conf` ve `/etc/hosts` dosyalarının zaman damgalı yedeğini alır, `mobile.4bsozlesme.com` tanımını ekler, Apache yapılandırmasını test eder ve yeniden yükler.
