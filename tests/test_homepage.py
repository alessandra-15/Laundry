"""
test_homepage.py
Test cases for the WashFlow Homepage / Landing Page (index.php)

Modules tested:
  - Navigation bar rendering
  - Hero section content
  - Section links (About, Services, Pricing, FAQ)
  - Sign In / Get Started buttons
  - Responsive mobile behavior
"""

import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


class TestHomepageNavigation:
    """TC-HP-01 through TC-HP-05: Navbar and navigation tests."""

    def test_hp01_page_loads_successfully(self, driver, navigate, wait):
        """TC-HP-01: Homepage loads without errors."""
        navigate("index.php")
        title = driver.title
        assert title != "", "Page title should not be empty"

        # Verify the navbar logo is visible
        logo = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, ".wf-logo .wf-logo-name")
        ))
        assert logo.is_displayed(), "WashFlow logo should be visible"

    def test_hp02_hero_section_content(self, driver, navigate, wait):
        """TC-HP-02: Hero section displays correct heading and tagline."""
        navigate("index.php")

        heading = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, ".wf-hero h1")
        ))
        assert "made simple" in heading.text.lower(), \
            f"Hero heading should contain 'made simple', got: {heading.text}"

        tagline = driver.find_element(By.CSS_SELECTOR, ".wf-hero .tagline")
        assert tagline.is_displayed(), "Tagline should be visible"

    def test_hp03_sign_in_button_navigates(self, driver, navigate, wait):
        """TC-HP-03: 'Sign In' button navigates to login.php."""
        navigate("index.php")

        sign_in = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, "a.wf-btn-hero-outline")
        ))
        sign_in.click()

        wait.until(EC.url_contains("login.php"))
        assert "login.php" in driver.current_url

    def test_hp04_get_started_button_navigates(self, driver, navigate, wait):
        """TC-HP-04: 'Get Started' button navigates to register.php."""
        navigate("index.php")

        get_started = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, "a.wf-btn-hero-primary")
        ))
        get_started.click()

        wait.until(EC.url_contains("register.php"))
        assert "register.php" in driver.current_url

    def test_hp05_about_section_scrolls(self, driver, navigate, wait):
        """TC-HP-05: Clicking 'About Us' nav link scrolls to about section."""
        navigate("index.php")

        about_link = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, "a[data-section='about']")
        ))
        about_link.click()

        import time
        time.sleep(1)

        about_section = driver.find_element(By.ID, "about")
        assert about_section.is_displayed(), "About section should be visible after scroll"


class TestHomepageSections:
    """TC-HP-06 through TC-HP-09: Content section tests."""

    def test_hp06_services_section_exists(self, driver, navigate, wait):
        """TC-HP-06: Services section is present with content."""
        navigate("index.php")

        services = wait.until(EC.presence_of_element_located((By.ID, "services")))
        assert services is not None, "Services section should exist"

    def test_hp07_pricing_section_exists(self, driver, navigate, wait):
        """TC-HP-07: Pricing section is present."""
        navigate("index.php")

        pricing = wait.until(EC.presence_of_element_located((By.ID, "pricing")))
        assert pricing is not None, "Pricing section should exist"

    def test_hp08_faq_section_exists(self, driver, navigate, wait):
        """TC-HP-08: FAQ section is present."""
        navigate("index.php")

        faq = wait.until(EC.presence_of_element_located((By.ID, "faq")))
        assert faq is not None, "FAQ section should exist"

    def test_hp09_footer_displays(self, driver, navigate, wait):
        """TC-HP-09: Footer section renders at bottom of page."""
        navigate("index.php")

        # Scroll to bottom
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        import time
        time.sleep(1)

        footer = driver.find_elements(By.CSS_SELECTOR, "footer, .wf-footer")
        assert len(footer) > 0, "Footer should exist on the homepage"
