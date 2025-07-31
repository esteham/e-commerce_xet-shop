document.addEventListener("DOMContentLoaded", function () {
  const contactForm = document.getElementById("contactForm");

  if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
      e.preventDefault();

      // Reset validation
      const formInputs = contactForm.querySelectorAll(
        ".form-control, .form-select"
      );
      formInputs.forEach((input) => {
        input.classList.remove("is-invalid");
      });

      // Validate form
      let isValid = true;
      const formData = new FormData(contactForm);

      // Name validation
      if (!formData.get("name")) {
        document.getElementById("name").classList.add("is-invalid");
        isValid = false;
      }

      // Email validation
      const email = formData.get("email");
      if (!email) {
        document.getElementById("email").classList.add("is-invalid");
        isValid = false;
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById("email").classList.add("is-invalid");
        document.getElementById("email").nextElementSibling.textContent =
          "Please enter a valid email address.";
        isValid = false;
      }

      // Subject validation
      if (!formData.get("subject")) {
        document.getElementById("subject").classList.add("is-invalid");
        isValid = false;
      }

      // Message validation
      if (!formData.get("message")) {
        document.getElementById("message").classList.add("is-invalid");
        isValid = false;
      } else if (formData.get("message").length < 10) {
        document.getElementById("message").classList.add("is-invalid");
        document.getElementById("message").nextElementSibling.textContent =
          "Your message should be at least 10 characters long.";
        isValid = false;
      }

      if (isValid) {
        // Submit form via AJAX
        submitForm(contactForm);
      }
    });
  }

  function submitForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;

    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

    fetch(form.action, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Show success message
          const successAlert = document.createElement("div");
          successAlert.className = "alert alert-success mt-3";
          successAlert.textContent = data.message;
          form.parentNode.insertBefore(successAlert, form.nextSibling);

          // Reset form
          form.reset();

          // Remove success message after 5 seconds
          setTimeout(() => {
            successAlert.remove();
          }, 5000);
        } else if (data.errors) {
          // Show validation errors
          for (const field in data.errors) {
            const input = document.getElementById(field);
            if (input) {
              input.classList.add("is-invalid");
              const feedback = input.nextElementSibling;
              if (feedback) {
                feedback.textContent = data.errors[field];
              }
            }
          }
        } else {
          // Show generic error
          const errorAlert = document.createElement("div");
          errorAlert.className = "alert alert-danger mt-3";
          errorAlert.textContent =
            "There was an error submitting your form. Please try again.";
          form.parentNode.insertBefore(errorAlert, form.nextSibling);

          // Remove error message after 5 seconds
          setTimeout(() => {
            errorAlert.remove();
          }, 5000);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        const errorAlert = document.createElement("div");
        errorAlert.className = "alert alert-danger mt-3";
        errorAlert.textContent =
          "There was an error submitting your form. Please try again.";
        form.parentNode.insertBefore(errorAlert, form.nextSibling);

        // Remove error message after 5 seconds
        setTimeout(() => {
          errorAlert.remove();
        }, 5000);
      })
      .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      });
  }

  // Real-time validation
  const inputs = document.querySelectorAll(
    "#contactForm .form-control, #contactForm .form-select"
  );
  inputs.forEach((input) => {
    input.addEventListener("input", function () {
      if (this.classList.contains("is-invalid")) {
        this.classList.remove("is-invalid");
      }
    });
  });
});
