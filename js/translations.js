// Translation system for Digital Signage
const Translations = {
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

  setLocale(locale) {
    return this.init(locale);
  }
};

// Initialize translations on page load
document.addEventListener('DOMContentLoaded', () => {
  // Get locale from document language or browser language
  let locale = document.documentElement.lang || navigator.language.split('-')[0] || 'en';
  
  // Map locale to supported languages
  if (locale.startsWith('de')) {
    locale = 'de';
  } else {
    locale = 'en';
  }
  
  Translations.init(locale);
});
