# Refactor Git Kurallari - Unutma Notu

Bu dosya ozellikle unutmayalim diye var.

## En onemli kural

Refactor isleri `main` uzerinde yapilmaz.

```text
main = calisan ana hat
codex/architecture-refactor = mimari donusum calisma hatti
```

Kararsiz kalirsak `main` yerine branch kullaniriz.

## Refactor baslamadan once

- [ ] `git status --short` temiz mi?
- [ ] Su anda hangi branch'teyiz?
- [ ] Refactor icin `codex/architecture-refactor` branch'i acik mi?
- [ ] Canli sunucuya refactor bitene kadar dosya gondermeyecegimizi hatirladik mi?

## Her committen once

- [ ] Degisen dosyalar listelendi.
- [ ] Kullaniciya hangi dosyalar degisti soylendi.
- [ ] PHP syntax kontrolu yapildi.
- [ ] Gerekliyse JS syntax kontrolu yapildi.
- [ ] Public ana sayfa/Hikayeler/Hikaye detay kontrol edildi.
- [ ] Admin tarafinda ilgili ekran kontrol edildi.

## Canliya gonderme kurali

Refactor bitene kadar canliya gonderme yok.

Refactor bittiginde once karar ver:

- Kucuk guncelleme mi?
- Temiz canli kurulum mu?

Buyuk refactor sonrasi tercih edilen yol:

1. Sunucudaki mevcut kodu yedekle.
2. Canli SQLite dosyasini yedekle.
3. `public/uploads/` klasorunu yedekle.
4. Eski kod kalintilarini temizle.
5. Yeni kodu gonder.
6. SQLite ve uploads dosyalarini yerine koy.
7. `public/deploy-manifest.json` dosyasini gonder.
8. Public ana sayfa, Hikayeler, Hikaye detay ve admin local akisini test et.

## Codex icin zorunlu hatirlatma

Ahmet unutabilir. Her refactor adiminda once bunu hatirlat:

```text
Bu is refactor branch'inde mi?
Main temiz mi?
Canliya henuz gondermuyoruz.
```

Refactor bittiginde bunu hatirlat:

```text
Simdi canliya cikis plani secilecek:
1. Kucuk guncelleme
2. Temiz canli kurulum
```
