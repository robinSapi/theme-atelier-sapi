
const themes = ["theme-terracotta", "theme-sage", "theme-mist"];

document.querySelectorAll("[data-theme]").forEach((btn) => {
  btn.addEventListener("click", () => {
    document.body.classList.remove(...themes);
    document.body.classList.add(btn.dataset.theme);
  });
});
