// Translation system for Digital Signage Settings
const SettingsTranslations = {
  translations: {},
  locale: 'en',

  async init(locale = 'en') {
    this.locale = locale;
    try {
      const response = await fetch(`/apps/digitalsignage/l10n/${locale}.json`);
      if (response.ok) {
        this.translations = await response.json();
      }
    } catch (error) {
      console.warn(`Could not load translations for locale ${locale}:`, error);
      this.translations = {};
    }
  },

  t(key, fallback = null) {
    if (this.translations && this.translations[key]) {
      return this.translations[key];
    }
    return fallback !== null ? fallback : key;
  },

  // Format messages with translations
  getMessage(type = 'success', key = 'Settings saved successfully') {
    const message = this.t(key);
    return `<span class="ds-message-${type}">${message}</span>`;
  }
};

// Initialize translations on page load
document.addEventListener('DOMContentLoaded', () => {
  // Get locale from data attribute
  const dataElement = document.querySelector('[data-locale]');
  let locale = dataElement ? dataElement.getAttribute('data-locale') : 'en';
  
  // Ensure locale is supported
  if (!['de', 'en'].includes(locale)) {
    locale = 'en';
  }
  
  SettingsTranslations.init(locale);
});
