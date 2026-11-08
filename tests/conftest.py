"""
conftest.py
Shared Appium fixtures for WashFlow Laundry System tests.
Uses Chrome browser on Android Studio emulator.

Run tests:
    python -m pytest tests/ -v
"""

import os
import time
from urllib.parse import urlparse

from selenium.webdriver.remote.webelement import WebElement
import pytest

from appium import webdriver
from appium.options.android import UiAutomator2Options

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


# ============================================================
# CONFIGURATION
# ============================================================

BASE_URL = os.environ.get("WASHFLOW_BASE_URL", "http://172.20.10.6/Laundry").rstrip("/")
APPIUM_SERVER = "http://127.0.0.1:4723"

# Test credentials — change these to match your database.
TEST_CUSTOMER_EMAIL = os.environ.get("WASHFLOW_TEST_CUSTOMER_EMAIL", "testcustomer@example.com")
TEST_CUSTOMER_PASSWORD = os.environ.get("WASHFLOW_TEST_CUSTOMER_PASSWORD", "Test@1234")

TEST_ADMIN_USERNAME = os.environ.get("WASHFLOW_TEST_ADMIN_USERNAME", "admin")
TEST_ADMIN_PASSWORD = os.environ.get("WASHFLOW_TEST_ADMIN_PASSWORD", "admin123")

IMPLICIT_WAIT = 5
EXPLICIT_WAIT = 15

# Hosts for which the tests may click through Chrome's Safe Browsing
# "Dangerous site" warning. Only our own deployed site belongs here.
# Remove this once Google clears the site's Safe Browsing flag.
SAFE_BROWSING_BYPASS_HOSTS = {"washflowlaundry.ct.ws"}


# ============================================================
# HELPER FUNCTION
# ============================================================

def safe_click(driver, wait, locator):
    """
    Scroll an element into view and click it using JavaScript.
    Useful when mobile browser elements overlap.
    """
    element = wait.until(
        EC.presence_of_element_located(locator)
    )

    driver.execute_script(
        "arguments[0].scrollIntoView({block: 'center'});",
        element
    )

    wait.until(
        EC.visibility_of_element_located(locator)
    )

    element = driver.find_element(*locator)

    driver.execute_script(
        "arguments[0].click();",
        element
    )


def proceed_past_safe_browsing_warning(driver):
    """
    If Chrome shows its "Dangerous site" warning for our own deployed
    site, click Details -> "visit this unsafe site". Chrome remembers the
    choice for that host for the rest of the browser session.
    Does nothing for any other host or page.
    """
    host = urlparse(driver.current_url).hostname or ""
    if host not in SAFE_BROWSING_BYPASS_HOSTS:
        return
    if driver.title != "Security error":
        return

    # Chrome's warning page rejects normal WebDriver locators, so click
    # its buttons with JavaScript, falling back to native Android taps.
    try:
        driver.execute_script(
            "document.getElementById('details-button').click();"
            "document.getElementById('proceed-link').click();"
        )
    except Exception:
        from appium.webdriver.common.appiumby import AppiumBy
        web_context = driver.current_context
        driver.switch_to.context("NATIVE_APP")
        try:
            driver.find_element(
                AppiumBy.ANDROID_UIAUTOMATOR, 'new UiSelector().text("Details")'
            ).click()
            WebDriverWait(driver, EXPLICIT_WAIT).until(
                lambda d: d.find_elements(
                    AppiumBy.ANDROID_UIAUTOMATOR,
                    'new UiSelector().textContains("unsafe site")',
                )
            )[0].click()
        finally:
            driver.switch_to.context(web_context)

    WebDriverWait(driver, 60).until(
        lambda d: d.title != "Security error"
    )


# ============================================================
# APPIUM DRIVER FIXTURE
# ============================================================

@pytest.fixture(scope="session")
def driver():
    """
    Start an Appium session using Chrome on the Android emulator.
    """
    options = UiAutomator2Options()
    options.platform_name = "Android"
    options.device_name = "emulator-5554"
    options.browser_name = "Chrome"
    options.no_reset = True
    options.auto_grant_permissions = True

    drv = webdriver.Remote(
        APPIUM_SERVER,
        options=options
    )

    drv.implicitly_wait(IMPLICIT_WAIT)
    drv.set_page_load_timeout(60)
    drv.set_script_timeout(30)

    # Accept Chrome's warning once at the start so every test sees the site.
    drv.get(f"{BASE_URL}/index.php")
    proceed_past_safe_browsing_warning(drv)

    yield drv

    drv.quit()


@pytest.fixture(autouse=True)
def fresh_login_state(driver):
    """
    The browser is shared by all tests, so clear cookies before each test.
    Every test then starts logged out, as its steps assume.
    The host's "__test" anti-bot cookie is kept; deleting it makes every
    page go back through InfinityFree's browser check.
    """
    for cookie in driver.get_cookies():
        if cookie["name"] != "__test":
            driver.delete_cookie(cookie["name"])
    yield


# On a phone screen many buttons start below the visible area. Scroll an
# element into view before clicking it, the way a real user would.
_original_click = WebElement.click


def _scroll_then_click(self):
    try:
        # Close the on-screen keyboard left open by typing. Otherwise the
        # tap only dismisses the keyboard and never reaches the element.
        closed_keyboard = self.parent.execute_script(
            "const a = document.activeElement;"
            "if (a && a !== arguments[0] && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName))"
            "{ a.blur(); return true; } return false;",
            self,
        )
        if closed_keyboard:
            time.sleep(0.7)  # let the page re-layout after the keyboard closes
        self.parent.execute_script(
            "arguments[0].scrollIntoView({block: 'center', behavior: 'instant'});",
            self,
        )
    except Exception:
        pass
    _original_click(self)


WebElement.click = _scroll_then_click


# ============================================================
# WAIT FIXTURE
# ============================================================

@pytest.fixture
def wait(driver):
    """Provide an explicit wait for each test."""
    return WebDriverWait(driver, EXPLICIT_WAIT)


# ============================================================
# NAVIGATION FIXTURE
# ============================================================

@pytest.fixture
def navigate(driver):
    """Navigate to a page using a relative path."""

    def _go(path):
        url = f"{BASE_URL}/{path.lstrip('/')}"
        driver.get(url)
        proceed_past_safe_browsing_warning(driver)

        time.sleep(1)

    return _go


# ============================================================
# CUSTOMER LOGIN FIXTURE
# ============================================================

@pytest.fixture
def customer_login(driver, navigate, wait):
    """
    Log in using the test customer account.
    """
    navigate("login.php")

    email_field = wait.until(
        EC.visibility_of_element_located(
            (By.CSS_SELECTOR, "#wfEmail")
        )
    )

    email_field.clear()
    email_field.send_keys(TEST_CUSTOMER_EMAIL)

    password_field = wait.until(
        EC.visibility_of_element_located(
            (By.CSS_SELECTOR, "#wfPassword")
        )
    )

    password_field.clear()
    password_field.send_keys(TEST_CUSTOMER_PASSWORD)

    safe_click(
        driver,
        wait,
        (By.CSS_SELECTOR, "#wfLoginBtn")
    )

    time.sleep(2)

    return driver


# ============================================================
# ADMIN LOGIN FIXTURE
# ============================================================

@pytest.fixture
def admin_login(driver, navigate, wait):
    """
    Log in as admin through the unified login page.
    (admin_login.php now redirects to login.php, which uses #wfEmail
    for both email and username.)
    """
    navigate("login.php")

    username_field = wait.until(
        EC.visibility_of_element_located(
            (By.CSS_SELECTOR, "#wfEmail")
        )
    )

    username_field.clear()
    username_field.send_keys(TEST_ADMIN_USERNAME)

    password_field = wait.until(
        EC.visibility_of_element_located(
            (By.CSS_SELECTOR, "#wfPassword")
        )
    )

    password_field.clear()
    password_field.send_keys(TEST_ADMIN_PASSWORD)

    safe_click(
        driver,
        wait,
        (By.CSS_SELECTOR, "#wfLoginBtn")
    )

    time.sleep(2)

    return driver
