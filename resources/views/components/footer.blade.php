  <footer class="ftco-footer ftco-footer-2 ftco-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-md">
                    <div class="ftco-footer-widget mb-4">
                        <h2 class="ftco-footer-logo">A<span style="color: white;">-Pay</span></h2>
                        <p>A-Pay is a leading platform for buying data, airtime, and paying bills online. A-Pay is a sub company of AfricGEM International Company Limited (AfricICL)</p>
                        <ul class="ftco-footer-social list-unstyled mt-2">
                            <li class="ftco-animate"><a href="https://africtv.com.ng/account/@AfricPay" target="_blank"><span class="fa fa-a"></span></a>Subscribe to our account on AfricTv</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md">
                    <div class="ftco-footer-widget mb-4 ml-md-5">
                        <h2 class="ftco-heading-2">Quick Links</h2>
                        <ul class="list-unstyled">
                            <li><a href="{{ url('about') }}" class="py-2 d-block">About</a></li>
                            <li><a href="{{ url('blog') }}" class="py-2 d-block">Blog</a></li>
                            <li><a href="{{ url('contact') }}" class="py-2 d-block">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md">
                    <div class="ftco-footer-widget mb-4">
                        <h2 class="ftco-heading-2">Legal</h2>
                        <ul class="list-unstyled">
                            <li><a href="#" class="py-2 d-block">Privacy &amp; Policy</a></li>
                            <li><a href="#" class="py-2 d-block">Terms &amp; Conditions</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md">
                    <div class="ftco-footer-widget mb-4">
                        <h2 class="ftco-heading-2">Have a Questions?</h2>
                        <div class="block-23 mb-3">
                            <ul>
                                <li><span class="icon fa fa-map marker"></span><span class="text">Lagos Nigeria</span></li>
                                <li><a href="#"><span class="icon fa fa-phone"></span><span class="text">+234 803 590 6313</span></a></li>
                                <li><a href="#"><span class="icon fa fa-paper-plane pr-4"></span><span class="text">africteam@gmail.com</span></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center">

                    <p>
                        Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved  <a href="https://africicl.com.ng" target="_blank">A-Pay</a> | Developed by <a href="https://africicl.com.ng" target="_blank">AfricTech</a>
                       
                    </div>
                </div>
            </div>
        </footer>
        
        

        <!-- loader -->
        <div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>
         <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
         <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
         <script>
            $(document).ready(function() {
                $('#contactUsForm').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var submitButton = form.find('button[type="submit"]');
                    submitButton.prop('disabled', true).text('Submitting...');

                    $.ajax({
                        url: '/contact-us',
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

        <script src="asset/js/jquery.min.js"></script>
        <script src="asset/js/jquery-migrate-3.0.1.min.js"></script>
        <script src="asset/js/popper.min.js"></script>
        <script src="asset/js/bootstrap.min.js"></script>
        <script src="asset/js/jquery.easing.1.3.js"></script>
        <script src="asset/js/jquery.waypoints.min.js"></script>
        <script src="asset/js/jquery.stellar.min.js"></script>
        <script src="asset/js/owl.carousel.min.js"></script>
        <script src="asset/js/jquery.magnific-popup.min.js"></script>
        <script src="asset/js/jquery.animateNumber.min.js"></script>
        <script src="asset/js/scrollax.min.js"></script>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&sensor=false"></script>
        <script src="asset/js/google-map.js"></script>
        <script src="asset/js/main.js"></script>
        
    </body>
    </html>