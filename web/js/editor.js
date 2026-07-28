// Editor WYSIWYG simple (contenteditable + execCommand), sin dependencias
// externas. Sincroniza el HTML editado a un <textarea> oculto antes de
// enviar el formulario, que es lo que realmente viaja al servidor.
document.querySelectorAll('.wysiwyg-editor').forEach((wrapper) => {
  const editable = wrapper.querySelector('.wysiwyg-content');
  const targetId = wrapper.dataset.target;
  const hiddenField = document.getElementById(targetId);
  if (!editable || !hiddenField) return;

  editable.innerHTML = hiddenField.value;

  wrapper.querySelectorAll('[data-command]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const command = btn.dataset.command;
      if (command === 'createLink') {
        const url = prompt('URL del link:');
        if (url) document.execCommand('createLink', false, url);
      } else if (command === 'insertImage') {
        const url = prompt('URL de la imagen:');
        if (url) document.execCommand('insertImage', false, url);
      } else {
        document.execCommand(command, false, btn.dataset.value || undefined);
      }
      editable.focus();
    });
  });

  const form = wrapper.closest('form');
  if (form) {
    form.addEventListener('submit', () => {
      hiddenField.value = editable.innerHTML;
    });
  }
});
