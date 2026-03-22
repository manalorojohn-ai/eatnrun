## Android App wrapper for online-food-ordering

This Android project wraps your existing PHP website in a WebView so it looks and behaves like a native app while preserving the same pages and design.

### What you get
- Login, Customer, Staff, and Admin Java Activities
- Shared WebView base with cookies, JS, and storage enabled
- Internet permission and cleartext (HTTP) support for local XAMPP
- Easy URL configuration via `res/values/strings.xml`

### Requirements
- Android Studio (latest stable)
- Android SDK 24+ (minSdk 24), targetSdk 34
- Your PHP site running and reachable from the device/emulator

### Project structure
```
android-app/
  settings.gradle
  build.gradle
  gradle.properties
  app/
    build.gradle
    src/main/
      AndroidManifest.xml
      java/com/onlinefoodordering/
        BaseWebViewActivity.java
        LoginActivity.java
        CustomerActivity.java
        StaffActivity.java
        AdminActivity.java
      res/
        layout/activity_webview.xml
        values/strings.xml
        values/colors.xml
        values/themes.xml
      xml/network_security_config.xml
```

### Configure your base URL
Edit `app/src/main/res/values/strings.xml` and set `base_url` to your server.

- For XAMPP on Windows with Android Emulator:
  - Use `http://10.0.2.2/online-food-ordering/` (emulator loopback to host)
- For a real device on same Wi‑Fi:
  - Find your PC IPv4 (e.g. `192.168.1.50`) and use `http://192.168.1.50/online-food-ordering/`
- For HTTPS domain:
  - Use your `https://your-domain/...` URL

Example:
```
<string name="base_url">http://10.0.2.2/online-food-ordering/</string>
```

### Role start pages
Update these if your start pages differ:
- Login: `login.php`
- Customer: `index.php` (or `menu.php`)
- Staff: `dashboard.php` (or `riders/...` if staff portal lives there)
- Admin: `admin/index.php`

You can change these in each Activity’s `getStartPath()` override.

### Run the app
1) Open Android Studio → Open → select the `android-app` folder.
2) Let Gradle sync. If prompted, install missing SDKs.
3) Ensure your PHP site is running and reachable at the `base_url` you set.
4) Select a device/emulator and click Run.

### Navigation and behavior
- Each screen is a WebView that loads the configured URL.
- Cookies and session auth work within the app via `CookieManager`.
- Android back button navigates WebView history; exits if no history.
- Use the overflow menu (⋮) → Reload to refresh a page.

### Cleartext HTTP (for local dev)
The app allows HTTP for `10.0.2.2` and private LAN ranges via `network_security_config.xml`. For production, switch to HTTPS and remove cleartext.

### Troubleshooting
- Blank page: Check that the URL is reachable from the device; try opening it in Chrome on the device.
- Login not sticking: Ensure PHP session cookies are set for the base URL; verify device time is correct.
- Mixed content blocked: If using HTTPS base with HTTP assets, fix the site to serve HTTPS assets.
- File uploads/camera: Not implemented in this minimal wrapper. Add a `WebChromeClient` with file chooser if needed.

### Build release APK
1) In Android Studio: Build → Generate Signed Bundle / APK…
2) Create a keystore if needed, choose APK, and complete the wizard.
3) The signed APK will be in `app/release/`.


