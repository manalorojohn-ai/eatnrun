package com.onlinefoodordering;

public class StaffActivity extends BaseWebViewActivity {
    @Override
    protected String getTitleText() {
        return getString(R.string.title_staff);
    }

    @Override
    protected String getStartPath() {
        return "dashboard.php"; // adjust if staff portal has another start page
    }
}


