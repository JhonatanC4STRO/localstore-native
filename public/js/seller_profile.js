function toggleFollow(btn) {
    const following = btn.classList.toggle('following');
    btn.innerHTML = following
      ? '<i class="bi bi-person-check-fill"></i> Siguiendo'
      : '<i class="bi bi-person-plus-fill"></i> Seguir';
  }

