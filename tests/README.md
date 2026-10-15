# WashFlow — Appium Test Suite

Automated mobile UI testing for the WashFlow Laundry Management System using **Appium** + **Android Studio Emulator** + **pytest**.

## Test Coverage Summary

| Module | File | Test Cases | Description |
|---|---|---|---|
| Homepage | `test_homepage.py` | TC-HP-01 to TC-HP-09 | Navigation, hero section, section links, footer |
| Customer Login | `test_login.py` | TC-LG-01 to TC-LG-11 | Valid/invalid login, empty fields, password toggle, remember me |
| Registration | `test_register.py` | TC-RG-01 to TC-RG-15 | 3-step wizard, field validation, account types, terms |
| Admin Login | `test_admin_login.py` | TC-AL-01 to TC-AL-09 | Admin auth, CSRF token, invalid credentials |
| Booking | `test_booking.py` | TC-BK-01 to TC-BK-10 | Access control, services, delivery options, form fields |
| Dashboard | `test_dashboard.py` | TC-CD/AD-01 to 05 | Customer & admin dashboard access and navigation |
| Feedback | `test_feedback.py` | TC-FB-01 to TC-FB-09 | Customer feedback, admin management, star ratings |
| Payments | `test_payments.py` | TC-PY-01 to TC-PY-08 | Payment history, GCash, admin payment management |

**Total: 66 test cases across 8 modules**

---

## Prerequisites

### 1. Android Studio Emulator
- Install [Android Studio](https://developer.android.com/studio)
- Create an emulator (e.g., **Pixel 7 API 34**)
- Start the emulator

### 2. Appium Server
```bash
npm install -g appium
appium driver install uiautomator2
appium --relaxed-security
```

### 3. Python Dependencies
```bash
cd tests
pip install -r requirements.txt
```

### 4. PHP Server Running
```bash
cd c:\Users\jhayv\OneDrive\Documents\GitHub\Laundry
php -S localhost:8000
```

### 5. Test Database
- Import `laundry_db (5).sql` into MySQL
- Ensure test credentials exist in the database (see `conftest.py`)

---

## Configuration

Edit `tests/conftest.py` to set:

```python
BASE_URL = "http://10.0.2.2:8000"    # 10.0.2.2 = host from Android emulator
TEST_CUSTOMER_EMAIL = "your_test_email@example.com"
TEST_CUSTOMER_PASSWORD = "YourPassword123"
TEST_ADMIN_USERNAME = "admin"
TEST_ADMIN_PASSWORD = "admin123"
```

> **Note:** `10.0.2.2` is the special IP that maps to your host machine's `localhost` from inside the Android emulator.

---

## Running Tests

### Run all tests
```bash
pytest tests/ -v
```

### Run a specific module
```bash
pytest tests/test_login.py -v
pytest tests/test_register.py -v
pytest tests/test_booking.py -v
```

### Run a specific test case
```bash
pytest tests/test_login.py::TestCustomerLoginValid::test_lg02_valid_login_redirects_to_dashboard -v
```

### Generate HTML report (optional)
```bash
pip install pytest-html
pytest tests/ -v --html=report.html --self-contained-html
```

---

## Test Architecture

```
tests/
├── conftest.py           # Shared fixtures (driver, login helpers, navigation)
├── requirements.txt      # Python dependencies
├── __init__.py
├── test_homepage.py      # Homepage / landing page tests
├── test_login.py         # Customer login tests
├── test_register.py      # Customer registration tests
├── test_admin_login.py   # Admin login tests
├── test_booking.py       # Booking module tests
├── test_dashboard.py     # Customer & admin dashboard tests
├── test_feedback.py      # Feedback module tests
└── test_payments.py      # Payment module tests
```

### Key Design Decisions
- **Chrome on Android emulator** — tests the web app through the mobile browser, simulating real mobile usage
- **Session-scoped driver** — one Appium connection shared across all tests for speed
- **Fixture-based login** — `customer_login` and `admin_login` fixtures handle authentication so tests stay clean
- **Explicit waits** — uses `WebDriverWait` for reliable element detection on slow emulators
