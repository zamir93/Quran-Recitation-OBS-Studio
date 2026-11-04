# Quran Recitation Studio for OBS Studio — Version 1.1 Islamic Themes Edition

**Author:** Mohammad Zamir Akbari  
**Author website:** https://z.akbari.org

A ready-to-run OBS browser-based dock + overlay. This is **not a native OBS `.dll`/`.so` plugin**; it uses OBS's built-in Custom Browser Docks and Browser Source.

## Requirements
- OBS Studio with Browser Source and Custom Browser Docks
- PHP 8.1+ (`php` on your PATH), or Apache/PHP via XAMPP or existing hosting
- Internet access to `api.alquran.cloud` and `cdn.islamic.network` for Quran data and audio
- Writable `data/` folder. Do **not** publish this app directly to an untrusted public network without authentication (the control API has no login).

## Windows
1. Unzip to a folder, e.g. `C:\Quran-OBS-Studio`.
2. Install PHP 8.1+ if necessary (or use XAMPP/Apache). Double-click `start-windows.bat`.
3. OBS → **Docks** → **Custom Browser Docks**, name `Quran Control` and URL `http://127.0.0.1:8765/control-panel.html`.
4. OBS → Sources → Add → **Browser** and enter `http://127.0.0.1:8765/overlay.html`. Set **Width 1920** and **Height 1080**.
5. In Browser Source settings, enable **Control audio via OBS**, and uncheck **Shutdown source when not visible** if you want continuous playback.
6. In the dock select Surah/Ayah and click **Show overlay** or **Play recitation**.
7. Keep `start-windows.bat` running during your broadcast.

## Linux/macOS
Run `./start-linux.sh` (requires PHP CLI). Then use the same OBS URLs.

## Features
- Surah list 1–114, ayah selection; 5 interface languages: German, English, Farsi/Dari, Arabic and Turkish; RTL support
- Arabic Uthmani Quran text with translations (selected editions: German, English, Persian, Turkish, Urdu) subject to API availability
- Three selectable reciters (subject to API edition availability); per-ayah playback, auto-advance, pause and stop
- Ten overlay themes with Islamic visual styles (Royal Blue, Emerald Mosque, Kaaba Night, Ottoman Blue, Desert Noor, Royal Arch, Glass Mihrab, Parchment Manuscript, Minimal Noor and Golden Night), six transitions, adjustable Arabic font size, toggle translations and references, volume control
- State syncing through local PHP endpoint and a JSON file; Quran metadata and ayah audio require a network connection

## Important limitations
- **Not tested inside OBS** on the user's computer. PHP syntax can be checked via `php -l` and web endpoints through a local server; actual browser audio/autoplay and Custom Docks must be tested in OBS.
- External Quran/translation/recitation endpoints may change or temporarily fail; all text is taken as delivered by the API without automated translation. Verify translations and audio alignment before broadcasting.
- Playback happens inside the **overlay** browser source, not in the dock; the audio source is controlled separately in OBS. In OBS audio mixer, enable/adjust Browser Source audio.
- Browser autoplay policies may prevent automatic audio playback until browser permission/OBS interaction allows it.
- The backend is unprotected; bind locally (default) or secure it with authentication and HTTPS before any remote hosting.
- Fonts are fetched via Google Fonts, with local system fallbacks.

## Sources
- [AlQuran Cloud API](https://alquran.cloud/api)
- [AlQuran Cloud CDN](https://alquran.cloud/cdn)
- [OBS Browser Source](https://obsproject.com/kb/browser-source)

## Files
`control-panel.html` = OBS Dock; `overlay.html` = stream overlay; `api.php` = backend; `assets/` = code; `data/` = cached responses and state; launch scripts included.
