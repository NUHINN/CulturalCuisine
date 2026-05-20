document.addEventListener("DOMContentLoaded", () => {
    const signUpButton = document.getElementById("signUpButton");
    const signInButton = document.getElementById("signInButton");
    const signInForm = document.getElementById("signIn");
    const signUpForm = document.getElementById("signup");

    if (signUpButton && signInButton && signInForm && signUpForm) {
        signUpButton.addEventListener("click", () => {
            signInForm.classList.remove("active");
            signUpForm.classList.add("active");
        });

        signInButton.addEventListener("click", () => {
            signUpForm.classList.remove("active");
            signInForm.classList.add("active");
        });
    }

    const deleteModalEl = document.getElementById("confirmDeleteModal");
    const confirmDeleteButton = document.getElementById("confirmDeleteButton");
    let pendingDeleteForm = null;

    if (deleteModalEl && confirmDeleteButton && window.bootstrap) {
        const deleteModal = new bootstrap.Modal(deleteModalEl);

        document.querySelectorAll("form.js-confirm-delete").forEach((form) => {
            form.addEventListener("submit", (event) => {
                if (form.dataset.confirmed === "true") {
                    return;
                }
                event.preventDefault();
                pendingDeleteForm = form;
                deleteModal.show();
            });
        });

        confirmDeleteButton.addEventListener("click", () => {
            if (!pendingDeleteForm) {
                return;
            }
            pendingDeleteForm.dataset.confirmed = "true";
            deleteModal.hide();
            pendingDeleteForm.submit();
        });
    } else {
        document.querySelectorAll("form.js-confirm-delete").forEach((form) => {
            form.addEventListener("submit", (event) => {
                if (!window.confirm("Delete this item? This action cannot be undone.")) {
                    event.preventDefault();
                }
            });
        });
    }

    document.querySelectorAll("[data-image-preview]").forEach((input) => {
        const target = document.querySelector(input.dataset.imagePreview);
        if (!target) {
            return;
        }

        input.addEventListener("change", () => {
            const file = input.files && input.files[0];
            if (!file || !file.type.startsWith("image/")) {
                return;
            }
            target.src = URL.createObjectURL(file);
        });
    });
});
