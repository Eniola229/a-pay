<style type="text/css">
          #contactUsButton {
          position: fixed;
          right: 20px;
          bottom: 80px; /* Adjust this value to move the button up or down */
          z-index: 1000;
          background-color: green; /* Bootstrap primary color */
          color: white;
          border: none;
          border-radius: 50px;
          padding: 15px 20px;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          font-size: 16px;
          display: flex;
          align-items: center;
          gap: 10px;
          transition: background-color 0.3s, box-shadow 0.3s;
        }

        #contactUsButton:hover {
          background-color: darkgreen; /* Darker shade for hover effect */
          box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
</style>
        <button id="contactUsButton" class="btn btn-primary">
          <i class="fas fa-envelope"></i>
        </button>
<!-- Contact Us Modal -->
<div class="modal fade" id="contactUsModal" tabindex="-1" aria-labelledby="contactUsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contactUsModalLabel">Contact Us | We are always here for you</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="contactUsForm">
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ Auth::user()->name ?? ' ' }}" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ Auth::user()->email ?? ' ' }}" required>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone_number" value="{{ Auth::user()->mobile ?? ' ' }}" required>
          </div>
          <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-control" id="category" name="category" required>
              <option value="">Select a category</option>
              <option value="General Inquiry">General Inquiry</option>
              <option value="Support">Support</option>
              <option value="Feedback">Feedback</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="complaint" class="form-label">Complaint/Suggestion</label>
            <textarea class="form-control" id="complaint" name="complaint" rows="3" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="background: green; border: none;">Submit</button>
        </form>
      </div>
    </div>
  </div>
</div>
 <script>
$(document).ready(function() {
    $('#contactUsButton').on('click', function() {
        var contactUsModal = new bootstrap.Modal(document.getElementById('contactUsModal'));
        contactUsModal.show();
    });

    $('#contactUsForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Submitting...');

        $.ajax({
            url: '/a-pay/contact-us',
            method: 'POST',
            data: form.serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                });
                form[0].reset();
                submitButton.prop('disabled', false).text('Submit');
                var contactUsModal = bootstrap.Modal.getInstance(document.getElementById('contactUsModal'));
                contactUsModal.hide();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.message || 'An error occurred. Please try again.',
                });
                submitButton.prop('disabled', false).text('Submit');
            }
        });
    });
});

</script>