package com.onlinefoodordering;

public class AdminActivity extends BaseWebViewActivity {
    @Override
    protected String getTitleText() {
        return getString(R.string.title_admin);
    }

    @Override
    protected String getStartPath() {
        return "admin/index.php"; // adjust if admin dashboard path differs
    }
}


