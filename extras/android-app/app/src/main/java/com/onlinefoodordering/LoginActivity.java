package com.onlinefoodordering;

public class LoginActivity extends BaseWebViewActivity {
    @Override
    protected String getTitleText() {
        return getString(R.string.title_login);
    }

    @Override
    protected String getStartPath() {
        return "login.php"; // adjust if your login page is different
    }
}


