@report @javascript @tool_cmcompetency
Feature: View the competencies report for an activity for student
  As a student

  Background:
    Given the cmcompetency fixtures exist
    And I log in as "rebeccaa"

  Scenario: Course module navigation
    Given I am on "Anatomy" course homepage
    And I follow "Competencies in course module"
    And I should see "Module 1" in the "//span[@aria-selected='true']" "xpath_element"
    And I should see "Module 1" in the "//h2" "xpath_element"
    And I should not see "No competencies have been linked to this course module."
    And "//ul[@class='form-autocomplete-suggestions']//li[contains(.,'Module 2')]" "xpath_element" should exist
    And "//ul[@class='form-autocomplete-suggestions']//li[contains(.,'Module 3')]" "xpath_element" should not exist
    When I click on ".form-autocomplete-downarrow" "css_element"
    And I click on "//ul[@class='form-autocomplete-suggestions']//li[contains(.,'Module 2')]" "xpath_element"
    Then I should see "Module 2" in the "//span[@aria-selected='true']" "xpath_element"
    And I should see "Module 2" in the "//h2" "xpath_element"
    And I should not see "No competencies have been linked to this course module."
    And I am on "Anatomy" course homepage
    And I follow "Forum Test"
    And I follow "Competencies in course module"
    And I should see "Forum Test" in the "//span[@aria-selected='true']" "xpath_element"
    And I should see "Forum Test" in the "//h2" "xpath_element"
    And I should see "No competencies have been linked to this course module."

  Scenario: Course module competencies
    Given I am on "Anatomy" course homepage
    # Module 1
    And I follow "Module 1"
    And I follow "Competencies in course module"
    And I should see "Module 1" in the "//h2" "xpath_element"
    And I should see "not good" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    # Module 1 competency stats.
    And I should see "You are proficient in 0 out of 1 competencies in this course module."
    When I click on "//a[contains(@class, 'collapse-link') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    Then I should not see "not good" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    And I should see "not good" in the "Evidence" of the competency "Competency A"
    And I should see "The competency rating was manually set in the course module" in the "Evidence" of the competency "Competency A"
    And I should see "No" in the "Proficient" of the competency "Competency A"
    And I click on "//a[contains(@class, 'collapse-link') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    And I should see "not good" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    And I should not see "No" in the "Proficient" of the competency "Competency A"
    # Module 2
    And I click on ".form-autocomplete-downarrow" "css_element"
    And I click on "//ul[@class='form-autocomplete-suggestions']//li[contains(.,'Module 2')]" "xpath_element"
    And I should see "Module 2" in the "//h2" "xpath_element"
    # Module 2 competency stats.
    And I should see "You are proficient in 1 out of 2 competencies in this course module."
    And I should see "Not rated" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    And I should see "qualified" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency B')]]" "xpath_element"
    And I should not see "No evidence" in the "Evidence" of the competency "Competency A"
    And I should not see "qualified" in the "Evidence" of the competency "Competency B"
    And I click on ".collapseexpand" "css_element"
    And I should not see "No rated" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency A')]]" "xpath_element"
    And I should not see "qualified" in the "//span[contains(@class, 'level') and ancestor-or-self::div/h4/a[contains(., 'Competency B')]]" "xpath_element"
    And I should see "No" in the "Proficient" of the competency "Competency A"
    And I should see "Yes" in the "Proficient" of the competency "Competency B"
    And I should see "-" in the "Rating" of the competency "Competency A"
    And I should see "qualified" in the "Rating" of the competency "Competency B"
    And I should see "No evidence" in the "Evidence" of the competency "Competency A"
    And I should see "qualified" in the "Evidence" of the competency "Competency B"
    And I should see "The competency rating was manually set in the course module" in the "Evidence" of the competency "Competency B"