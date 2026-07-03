            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h1 class="modal-title fs-5" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Confirmation de suppression
                    </h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Êtes-vous sûr de vouloir supprimer cet utilisateur ?</strong></p>
                    <p class="text-muted">Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Annuler
                    </button>
                    <a href="#" id="deleteLink" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gérer le modal de suppression
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const deleteUrl = button.getAttribute('data-delete-url');
                const deleteLink = document.getElementById('deleteLink');
                deleteLink.href = deleteUrl;
            });
        }
    </script>
    <script>
        // Select / Deselect all checkboxes
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="ids[]"]');
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }
    </script>
</body>
</html>
