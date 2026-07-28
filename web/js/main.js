const navToggle = document.getElementById('nav-toggle');
const mainNav = document.getElementById('main-nav');

navToggle.addEventListener('click', () => {
  const isOpen = mainNav.classList.toggle('open');
  navToggle.setAttribute('aria-expanded', String(isOpen));
});

mainNav.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => {
    mainNav.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  });
});

// Aviso de cookies/localStorage
(() => {
  const banner = document.getElementById('cookie-banner');
  if (!banner) return;
  const STORAGE_KEY = 'chateanos_cookie_consent';

  let accepted = false;
  try {
    accepted = localStorage.getItem(STORAGE_KEY) === '1';
  } catch (e) {
    accepted = false;
  }

  if (!accepted) {
    banner.hidden = false;
  }

  document.getElementById('cookie-accept').addEventListener('click', () => {
    try {
      localStorage.setItem(STORAGE_KEY, '1');
    } catch (e) {
      // Sin localStorage disponible: igual ocultamos el aviso para esta carga.
    }
    banner.hidden = true;
  });
})();

// Aviso de edad / NSFW: bloquea las salas de "adultos" hasta confirmar 18+.
(() => {
  const gate = document.getElementById('age-gate');
  const confirmBtn = document.getElementById('age-gate-confirm');
  if (!gate || !confirmBtn) return;
  const STORAGE_KEY = 'chateanos_age_confirmed';

  let confirmed = false;
  try {
    confirmed = localStorage.getItem(STORAGE_KEY) === '1';
  } catch (e) {
    confirmed = false;
  }

  if (confirmed) {
    gate.classList.add('is-confirmed');
  }

  confirmBtn.addEventListener('click', () => {
    try {
      localStorage.setItem(STORAGE_KEY, '1');
    } catch (e) {
      // Sin localStorage disponible: igual desbloqueamos para esta carga.
    }
    gate.classList.add('is-confirmed');
  });
})();

// Checklist de "primeros pasos": progreso guardado en localStorage.
(() => {
  const list = document.getElementById('onboarding-checklist');
  if (!list) return;
  const STORAGE_KEY = 'chateanos_onboarding';
  const boxes = Array.from(list.querySelectorAll('input[type=checkbox]'));
  const progressEl = document.getElementById('onboarding-progress');

  let done = {};
  try {
    done = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
  } catch (e) {
    done = {};
  }

  const updateProgress = () => {
    const checked = boxes.filter((b) => b.checked).length;
    if (progressEl) progressEl.textContent = `${checked} de ${boxes.length} completados`;
  };

  boxes.forEach((box) => {
    box.checked = !!done[box.dataset.step];
    box.addEventListener('change', () => {
      done[box.dataset.step] = box.checked;
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(done));
      } catch (e) {
        // Sin localStorage disponible: el progreso no persiste, pero no rompe nada.
      }
      updateProgress();
    });
  });

  updateProgress();
})();

// Modo lectura simplificada para artículos largos.
(() => {
  const btn = document.getElementById('reading-mode-toggle');
  const body = document.querySelector('.news-body');
  if (!btn || !body) return;

  const apply = (on) => {
    document.documentElement.classList.toggle('reading-mode', on);
    btn.setAttribute('aria-pressed', String(on));
    btn.textContent = on ? '📖 Modo normal' : '📖 Modo lectura';
  };

  let on = false;
  try {
    on = sessionStorage.getItem('chateanos_reading_mode') === '1';
  } catch (e) {
    on = false;
  }
  apply(on);

  btn.addEventListener('click', () => {
    on = !on;
    apply(on);
    try {
      sessionStorage.setItem('chateanos_reading_mode', on ? '1' : '0');
    } catch (e) {
      // Sin sessionStorage disponible: el modo no persiste entre páginas.
    }
  });
})();

// Tour guiado de bienvenida (solo home, una vez por navegador).
(() => {
  const steps = [
    { selector: '.hero-cta .btn-primary', text: 'Empezá por acá: entrás al webchat sin instalar nada.' },
    { selector: 'a[href="salas.php#general"]', text: 'Elegí una sala según lo que te interese charlar.' },
    { selector: 'a[href="/gestiones.php"]', text: 'Desde Gestiones podés pedir ayuda, apelar o abrir un ticket.' },
    { selector: 'a[href="conectar.php"]', text: '¿Preferís un cliente de escritorio? Acá tenés los datos de conexión.' },
  ];

  const STORAGE_KEY = 'chateanos_tour_seen';
  const tourRoot = document.querySelector('.hero');
  if (!tourRoot) return;

  let seen = false;
  try {
    seen = localStorage.getItem(STORAGE_KEY) === '1';
  } catch (e) {
    seen = false;
  }
  if (seen) return;

  let index = 0;
  let tooltip = null;

  const cleanup = () => {
    if (tooltip) tooltip.remove();
    tooltip = null;
    try {
      localStorage.setItem(STORAGE_KEY, '1');
    } catch (e) {
      // Sin localStorage: el tour puede volver a aparecer, no es grave.
    }
  };

  const showStep = () => {
    if (tooltip) tooltip.remove();
    if (index >= steps.length) {
      cleanup();
      return;
    }
    const step = steps[index];
    const target = document.querySelector(step.selector);
    if (!target) {
      index++;
      showStep();
      return;
    }
    tooltip = document.createElement('div');
    tooltip.className = 'onboarding-tooltip';
    tooltip.innerHTML = `<p>${step.text}</p>`;
    const nextBtn = document.createElement('button');
    nextBtn.className = 'btn btn-primary btn-sm';
    nextBtn.textContent = index === steps.length - 1 ? 'Listo' : 'Siguiente';
    nextBtn.addEventListener('click', () => { index++; showStep(); });
    const skipBtn = document.createElement('button');
    skipBtn.className = 'btn btn-ghost btn-sm';
    skipBtn.textContent = 'Saltar';
    skipBtn.addEventListener('click', cleanup);
    const actions = document.createElement('div');
    actions.className = 'onboarding-tooltip-actions';
    actions.append(skipBtn, nextBtn);
    tooltip.appendChild(actions);
    document.body.appendChild(tooltip);

    const rect = target.getBoundingClientRect();
    tooltip.style.top = `${window.scrollY + rect.bottom + 10}px`;
    tooltip.style.left = `${Math.max(12, window.scrollX + rect.left)}px`;
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  setTimeout(showStep, 900);
})();

// Widget "vistas recientes": pide el contador propio, sin terceros.
(() => {
  const widget = document.getElementById('recent-views-widget');
  if (!widget) return;
  fetch('/vistas-recientes.php?path=' + encodeURIComponent(widget.dataset.path || window.location.pathname))
    .then((r) => r.json())
    .then((data) => {
      if (data.count >= 2) {
        widget.textContent = `👀 ${data.count} personas vieron esto en los últimos 5 minutos`;
        widget.hidden = false;
      }
    })
    .catch(() => {});
})();

// Compartir noticia con la Web Share API nativa, si el navegador la soporta.
(() => {
  const btn = document.getElementById('native-share-btn');
  if (!btn) return;
  if (navigator.share) {
    btn.hidden = false;
    btn.addEventListener('click', () => {
      navigator.share({
        title: btn.dataset.text,
        url: btn.dataset.url,
      }).catch(() => {});
    });
  }
})();

// Botón "instalar app" (PWA) usando el evento nativo del navegador.
(() => {
  const installBtn = document.getElementById('pwa-install-btn');
  if (!installBtn) return;
  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    installBtn.hidden = false;
  });

  installBtn.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    installBtn.hidden = true;
  });

  window.addEventListener('appinstalled', () => {
    installBtn.hidden = true;
  });
})();

document.querySelectorAll('.copy-btn').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const value = btn.getAttribute('data-copy');
    try {
      await navigator.clipboard.writeText(value);
      const original = btn.textContent;
      btn.textContent = 'Copiado';
      btn.classList.add('copied');
      setTimeout(() => {
        btn.textContent = original;
        btn.classList.remove('copied');
      }, 1500);
    } catch (err) {
      // Clipboard API unavailable; nothing to fall back to silently.
    }
  });
});

// Reproductor de radio flotante, persistente (vía localStorage) entre páginas.
(() => {
  const widget = document.getElementById('radio-widget');
  if (!widget) return;

  const audio = document.getElementById('radio-audio');
  const toggleBtn = document.getElementById('radio-toggle');
  const muteBtn = document.getElementById('radio-mute');
  const STORAGE_KEY = 'chateanos_radio_state';

  const readState = () => {
    try {
      return { playing: true, muted: false, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
    } catch (e) {
      return { playing: true, muted: false };
    }
  };

  const writeState = (state) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {
      // localStorage no disponible (modo privado, etc.): seguimos sin persistir.
    }
  };

  let state = readState();

  const setUiPlaying = (isPlaying) => {
    widget.classList.toggle('is-playing', isPlaying);
    toggleBtn.setAttribute('aria-pressed', String(isPlaying));
    toggleBtn.setAttribute('aria-label', isPlaying ? 'Pausar radio' : 'Reproducir radio');
  };

  const setUiMuted = (isMuted) => {
    widget.classList.toggle('is-muted', isMuted);
    muteBtn.textContent = isMuted ? '🔇' : '🔊';
    muteBtn.setAttribute('aria-label', isMuted ? 'Activar sonido' : 'Silenciar');
  };

  audio.muted = state.muted;
  setUiMuted(state.muted);

  const attemptAutoplay = () => {
    if (!state.playing) return;
    audio.play().then(() => {
      setUiPlaying(true);
    }).catch(() => {
      // Bloqueado por la política de autoplay del navegador: esperamos un click.
      setUiPlaying(false);
      widget.classList.add('needs-interaction');
    });
  };

  audio.src = widget.dataset.src;
  attemptAutoplay();

  toggleBtn.addEventListener('click', () => {
    widget.classList.remove('needs-interaction');
    if (audio.paused) {
      audio.play().then(() => {
        state.playing = true;
        setUiPlaying(true);
        writeState(state);
      }).catch(() => {});
    } else {
      audio.pause();
      state.playing = false;
      setUiPlaying(false);
      writeState(state);
    }
  });

  muteBtn.addEventListener('click', () => {
    audio.muted = !audio.muted;
    state.muted = audio.muted;
    setUiMuted(state.muted);
    writeState(state);
  });
})();

// Carrusel de noticias (home): avanza solo, con flechas y puntos.
(() => {
  const carousel = document.getElementById('news-carousel');
  if (!carousel) return;

  const track = carousel.querySelector('.news-carousel-track');
  const slides = Array.from(carousel.querySelectorAll('.news-slide'));
  const dots = Array.from(document.querySelectorAll('#news-dots .carousel-dot'));
  const prevBtn = document.getElementById('news-prev');
  const nextBtn = document.getElementById('news-next');
  const AUTOPLAY_MS = 6000;

  let index = 0;
  let timer = null;

  const goTo = (newIndex) => {
    index = (newIndex + slides.length) % slides.length;
    track.style.transform = `translateX(-${index * 100}%)`;
    slides.forEach((slide, i) => {
      const isActive = i === index;
      slide.toggleAttribute('aria-hidden', !isActive);
      slide.tabIndex = isActive ? 0 : -1;
    });
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
  };

  const restartAutoplay = () => {
    if (slides.length < 2) return;
    clearInterval(timer);
    timer = setInterval(() => goTo(index + 1), AUTOPLAY_MS);
  };

  if (prevBtn) prevBtn.addEventListener('click', () => { goTo(index - 1); restartAutoplay(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { goTo(index + 1); restartAutoplay(); });
  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      goTo(parseInt(dot.dataset.index, 10));
      restartAutoplay();
    });
  });

  carousel.addEventListener('mouseenter', () => clearInterval(timer));
  carousel.addEventListener('mouseleave', restartAutoplay);

  goTo(0);
  restartAutoplay();
})();
