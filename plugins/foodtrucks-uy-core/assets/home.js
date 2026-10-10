/* No slider framework; scroll snap, keyboard-accessible buttons, no autoplay. */
document.querySelectorAll('.ft-hero').forEach(hero => {
  const track = hero.querySelector('.ft-hero-track');
  const move = delta => {
    const count = track.children.length;
    const index = Math.round(track.scrollLeft / track.clientWidth);
    track.scrollTo({left: ((index + delta + count) % count) * track.clientWidth, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
  };
  hero.querySelector('[data-hero-prev]').addEventListener('click', () => move(-1));
  hero.querySelector('[data-hero-next]').addEventListener('click', () => move(1));
});
let ftuyMapsPromise;
function ftuyLoadMaps(key) {
  if (window.google?.maps?.importLibrary) return Promise.resolve();
  if (!ftuyMapsPromise) ftuyMapsPromise = new Promise((resolve, reject) => {
    window.ftuyHomeMapsReady = resolve;
    const script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?' + new URLSearchParams({key, loading:'async', callback:'ftuyHomeMapsReady', v:'weekly', language:'es', region:'UY'});
    script.async = true;
    script.onerror = reject;
    document.head.append(script);
  });
  return ftuyMapsPromise;
}
document.querySelectorAll('.ft-home-map').forEach(element => {
  let started = false;
  const load = async () => {
    if (started) return;
    started = true;
    try {
      const points = JSON.parse(element.dataset.points);
      await ftuyLoadMaps(element.dataset.mapKey);
      const {Map, InfoWindow} = await google.maps.importLibrary('maps');
      const {LatLngBounds} = await google.maps.importLibrary('core');
      const {AdvancedMarkerElement} = await google.maps.importLibrary('marker');
      element.replaceChildren();
      const map = new Map(element, {center:points[0], zoom:12, mapId:element.dataset.mapId, gestureHandling:'cooperative', streetViewControl:false, mapTypeControl:false});
      const bounds = new LatLngBounds();
      const info = new InfoWindow();
      points.forEach(point => {
        bounds.extend(point);
        const marker = new AdvancedMarkerElement({map,position:point,title:point.title});
        marker.addListener('click', () => {
          const link = document.createElement('a'); link.textContent = point.title; link.href = point.url;
          info.setContent(link); info.open({map,anchor:marker});
        });
      });
      if (points.length > 1) map.fitBounds(bounds, 40);
    } catch (_) { element.textContent = 'No pudimos cargar el mapa. Podés abrir los eventos desde los enlaces debajo.'; }
  };
  element.querySelector('button').addEventListener('click', load);
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => { if(entries.some(e => e.isIntersecting)) { observer.disconnect(); load(); } }, {rootMargin:'150px'});
    observer.observe(element);
  }
});
