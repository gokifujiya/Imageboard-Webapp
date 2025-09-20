document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('update-part-form');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(form);

    fetch('/form/update/part', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          alert('Update successful!');
          if (!formData.has('id')) form.reset(); // only clear for "create"
        } else {
          alert('Update failed: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error(err);
        alert('An error occurred. Please try again.');
      });
  });
});

