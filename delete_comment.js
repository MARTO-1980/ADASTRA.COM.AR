async function deleteComment(commentId) {
  try {
    if (!window.currentTattooObj || !window.currentTattooObj.tattoo) return;
    const tattooId = btoa(window.currentTattooObj.tattoo.image.substring(0, 50));
    const res = await fetch('delete_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tattoo_id: tattooId,
        comment_id: commentId,
        user_email: window.currentUserEmail
      })
    });
    const result = await res.json();
    if (result.success) {
      if (typeof window.loadComments === 'function') {
        await window.loadComments(tattooId);
      }
      alert('Comentario eliminado');
    } else {
      alert('Error: ' + (result.error || 'No se pudo eliminar'));
    }
  } catch (e) {
    alert('Error de conexión: ' + e.message);
  }
}
