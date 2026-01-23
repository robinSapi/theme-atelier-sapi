(function () {
  const hero = document.querySelector('.hero');
  if (!hero) return;

  const images = hero.dataset.heroImages ? JSON.parse(hero.dataset.heroImages) : [];
  if (!images.length) return;

  let index = 0;
  hero.style.backgroundImage = `url('${images[index]}')`;

  setInterval(() => {
    index = (index + 1) % images.length;
    hero.classList.add('is-fading');
    setTimeout(() => {
      hero.style.backgroundImage = `url('${images[index]}')`;
      hero.classList.remove('is-fading');
    }, 400);
  }, 4000);
})();
