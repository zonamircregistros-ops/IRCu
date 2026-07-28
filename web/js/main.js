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
