<!-- Shared delete-confirmation modal. Openers: <button data-confirm-action="/x/1/delete" data-confirm-msg="..."> -->
<div class="modal-backdrop" id="confirm-modal">
  <div class="modal">
    <h3>Confirm deletion</h3>
    <p id="confirm-msg">Are you sure? This cannot be undone.</p>
    <form method="post" id="confirm-form">
      <?= Csrf::field() ?>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
