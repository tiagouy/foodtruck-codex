/* Match the app's person-icon fallback if a profile image cannot load. */
(() => {
  const fallback = image => {
    if (image instanceof HTMLImageElement && image.matches('.ft-photo-avatar img')) {
      image.hidden = true;
    }
  };
  document.addEventListener('error', event => fallback(event.target), true);
  document.querySelectorAll('.ft-photo-avatar img').forEach(image => {
    if (image.complete && image.naturalWidth === 0) fallback(image);
  });
})();
