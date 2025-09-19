// Utilitários para validação de formulários - EcoSwap
const FormValidator = {
  validateEmail: (email) => {
    // Regex mais rigorosa para email
    const regex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    return regex.test(email) && email.length <= 254;
  },

  validatePassword: (password) => {
    // Mantendo 6 caracteres para compatibilidade com dados de teste
    return password.length >= 6;
  },

  validateRequired: (value) => {
    return value && value.trim().length > 0;
  },

  validatePhone: (phone) => {
    const regex = /^\(\d{2}\)\s\d{4,5}-\d{4}$/;
    return regex.test(phone);
  },

  validateName: (name) => {
    // Apenas letras, espaços e acentos
    const regex = /^[a-zA-ZÀ-ÿ\s]{2,50}$/;
    return regex.test(name.trim());
  },

  showError: (field, message) => {
    const $field = $(field);
    $field.removeClass("is-valid").addClass("is-invalid");
    $field.siblings(".invalid-feedback").remove();
    $field.after(`<div class="invalid-feedback">${message}</div>`);
  },

  showSuccess: (field) => {
    const $field = $(field);
    $field.removeClass("is-invalid").addClass("is-valid");
    $field.siblings(".invalid-feedback").remove();
  },

  clearValidation: (form) => {
    $(form).find(".is-valid, .is-invalid").removeClass("is-valid is-invalid");
    $(form).find(".invalid-feedback").remove();
  },
};
