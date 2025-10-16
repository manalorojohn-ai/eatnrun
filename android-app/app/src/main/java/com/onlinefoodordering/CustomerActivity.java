package com.onlinefoodordering;

public class CustomerActivity extends BaseWebViewActivity {
    @Override
    protected String getTitleText() {
        return getString(R.string.title_customer);
    }

    @Override
    protected String getStartPath() {
        return "index.php"; // or "menu.php" if that is your landing page
    }
}


