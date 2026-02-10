/**
 * Focal Point Picker for Shop Hero Image
 * Shows image with draggable crosshair + live preview rectangle
 * Saves instantly via AJAX (Gutenberg compatible)
 */
(function () {
  'use strict';

  const HERO_RATIO = 3.5; // width:height ratio of hero section (~45vh on 1400px wide)

  function init() {
    const container = document.getElementById('sapi-focal-picker');
    if (!container) return;

    const img = container.querySelector('.focal-picker-image');
    const crosshair = container.querySelector('.focal-picker-crosshair');
    const preview = container.querySelector('.focal-picker-preview');
    const input = document.getElementById('sapi_hero_focal_point');
    const coordsDisplay = container.querySelector('.focal-picker-coords');
    const statusEl = container.querySelector('.focal-picker-status');

    if (!img || !input) return;

    // Parse initial value
    let focalX = 50, focalY = 50;
    const val = input.value;
    if (val) {
      const parts = val.split(' ');
      focalX = parseFloat(parts[0]) || 50;
      focalY = parseFloat(parts[1]) || 50;
    }

    let saveTimeout = null;

    function updatePicker(x, y, autoSave) {
      focalX = Math.max(0, Math.min(100, x));
      focalY = Math.max(0, Math.min(100, y));

      // Update crosshair position
      crosshair.style.left = focalX + '%';
      crosshair.style.top = focalY + '%';

      // Update hidden input
      input.value = focalX.toFixed(1) + '% ' + focalY.toFixed(1) + '%';

      // Update coordinates display
      if (coordsDisplay) {
        coordsDisplay.textContent = Math.round(focalX) + '% / ' + Math.round(focalY) + '%';
      }

      // Update preview rectangle
      updatePreview();

      // Auto-save via AJAX (debounced)
      if (autoSave) {
        if (typeof sapiFocal === 'undefined') {
          if (statusEl) {
            statusEl.style.color = '#dc3232';
            statusEl.textContent = 'AJAX non chargé — rechargez la page';
          }
          return;
        }
        if (saveTimeout) clearTimeout(saveTimeout);
        if (statusEl) {
          statusEl.style.color = '#666';
          statusEl.textContent = '...';
        }
        saveTimeout = setTimeout(saveViaAjax, 300);
      }
    }

    function saveViaAjax() {
      if (typeof sapiFocal === 'undefined') return;

      var formData = new FormData();
      formData.append('action', 'sapi_save_focal_point');
      formData.append('nonce', sapiFocal.nonce);
      formData.append('post_id', sapiFocal.postId);
      formData.append('focal_point', input.value);

      fetch(sapiFocal.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (statusEl) {
          if (data.success) {
            statusEl.style.color = '#46b450';
            statusEl.textContent = '✓ Sauvegardé (page ' + sapiFocal.postId + ')';
          } else {
            statusEl.style.color = '#dc3232';
            statusEl.textContent = '✗ ' + (data.data || 'Erreur inconnue');
          }
          setTimeout(function () { statusEl.textContent = ''; }, 4000);
        }
      })
      .catch(function (err) {
        if (statusEl) {
          statusEl.style.color = '#dc3232';
          statusEl.textContent = '✗ Réseau: ' + err.message;
        }
      });
    }

    function updatePreview() {
      if (!preview) return;

      const imgRect = img.getBoundingClientRect();
      const containerRect = container.querySelector('.focal-picker-area').getBoundingClientRect();
      const imgW = imgRect.width;
      const imgH = imgRect.height;

      if (imgW === 0 || imgH === 0) return;

      const imgRatio = imgW / imgH;

      // The hero crops the image with object-fit: cover
      // Calculate visible area based on hero ratio vs image ratio
      let visW, visH;
      if (imgRatio > HERO_RATIO) {
        // Image is wider than hero → height fits, width is cropped
        visH = imgH;
        visW = imgH * HERO_RATIO;
      } else {
        // Image is taller than hero → width fits, height is cropped
        visW = imgW;
        visH = imgW / HERO_RATIO;
      }

      // Position the preview rectangle centered on focal point
      const focalPxX = (focalX / 100) * imgW;
      const focalPxY = (focalY / 100) * imgH;

      let rectLeft = focalPxX - visW / 2;
      let rectTop = focalPxY - visH / 2;

      // Clamp to image bounds
      rectLeft = Math.max(0, Math.min(imgW - visW, rectLeft));
      rectTop = Math.max(0, Math.min(imgH - visH, rectTop));

      // Offset by image position within container
      const imgOffsetLeft = imgRect.left - containerRect.left;
      const imgOffsetTop = imgRect.top - containerRect.top;

      preview.style.left = (imgOffsetLeft + rectLeft) + 'px';
      preview.style.top = (imgOffsetTop + rectTop) + 'px';
      preview.style.width = visW + 'px';
      preview.style.height = visH + 'px';
      preview.style.display = 'block';
    }

    // Click / drag to set focal point
    const area = container.querySelector('.focal-picker-area');
    let isDragging = false;

    function setFromEvent(e) {
      const imgRect = img.getBoundingClientRect();
      const x = ((e.clientX - imgRect.left) / imgRect.width) * 100;
      const y = ((e.clientY - imgRect.top) / imgRect.height) * 100;
      updatePicker(x, y, true);
    }

    area.addEventListener('mousedown', function (e) {
      e.preventDefault();
      isDragging = true;
      setFromEvent(e);
    });

    document.addEventListener('mousemove', function (e) {
      if (isDragging) {
        e.preventDefault();
        setFromEvent(e);
      }
    });

    document.addEventListener('mouseup', function () {
      isDragging = false;
    });

    // Initialize (no auto-save on init)
    img.addEventListener('load', function () {
      updatePicker(focalX, focalY, false);
    });

    if (img.complete) {
      updatePicker(focalX, focalY, false);
    }

    window.addEventListener('resize', function () {
      updatePreview();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
