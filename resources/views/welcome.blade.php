@include('components.header-guest')
    <div class="hero-wrap js-fullheight" style="background-image: url('https://img.freepik.com/free-photo/smiley-woman-holding-phone-side-view_23-2149742234.jpg?t=st=1741398722~exp=1741402322~hmac=c9717686fd2aabd8b197283bf081630becdf3dc8bbe74b2724750601e7ab9c00&w=1060');" data-stellar-background-ratio="0.5">
        <div class="overlay"></div>
        <div class="container">
            <div class="row no-gutters slider-text js-fullheight align-items-center" data-scrollax-parent="true">
                <div class="col-md-8 ftco-animate mt-5 pt-md-5" data-scrollax=" properties: { translateY: '70%' }">
                    <div class="row">
                        <div class="col-md-7">
                            <p class="mb-4 pl-md-5 line" data-scrollax="properties: { translateY: '30%', opacity: 1.6 }">Welcome to A-Pay</p>
                        </div>
                    </div>
                    <h1 class="mb-4" data-scrollax="properties: { translateY: '30%', opacity: 1.6 }">Your all-in-one VTU assistant on WhatsApp</h1>
                    <p>Recharge airtime, buy data, and pay bills easily.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <!-- WhatsApp Version -->
                        <a href="" 
                           class="btn btn-primary d-flex align-items-center px-4 py-3" 
                           role="button">
                            <i class="fab fa-whatsapp fa-2x me-2"></i>
                            <span class="fw-bold"> Try A-Pay Now</span>
                        </a>

                        <!-- Web Version -->
                        <a href="{{ route('login') }}" 
                           class="btn btn-success d-flex align-items-center px-4 py-3" 
                           role="button">
                            <i class="fas fa-globe fa-2x me-2"></i>
                            <span class="fw-bold"> Use Web Version</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <section class="ftco-intro">
        <div class="container">
            <div class="row justify-content-end">
                <div class="col-md-7">
                    <div class="row no-gutters d-flex align-items-stretch">
                        <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                            <div class="services-1">
                                <div class="line"></div>
                                <div class="icon"><span class="flaticon-wifi"></span><i class="fas fa-wifi"></i></div>
                                <div class="media-body">
                                    <h3 class="heading mb-3">Purchase data bundles for all networks at the best rates.</h3>
                                </div>
                            </div>      
                        </div>
                        <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                            <div class="services-1 color-1">
                                <div class="line"></div>
                                <div class="icon"><span class="flaticon"><i class="fas fa-mobile-alt"></i></span></div>
                                <div class="media-body">
                                    <h3 class="heading mb-3">Recharge your phone instantly with our airtime services.</h3>
                                </div>
                            </div>      
                        </div>
                        <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                            <div class="services-1 color-2">
                                <div class="line"></div>
                                <div class="icon"><i class="fas fa-credit-card"></i></div>
                                <div class="media-body">
                                    <h3 class="heading mb-3">Pay electricity bills and other utilities seamlessly.</h3>
                                </div>
                            </div>      
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center pb-5">
            <div class="col-lg-6 heading-section text-center ftco-animate">
                <h2 class="mb-4">Your Trusted Partner for <span style="color: green;">Airtime, Data, and Online Payments</span></h2>
                <p style="">Experience seamless transactions for all your essential services, anytime and anywhere.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="services-2 text-center">
                    <div class="icon">
                        <span class="flaticon-smartphone"></span>
                    </div>
                    <div class="text">
                        <h3>Airtime Recharge</h3>
                        <p>Instantly top up your mobile airtime across all major networks with ease and convenience.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <img src="images/APAY.png" class="img-fluid" alt="A-pay Services">
            </div>
            <div class="col-md-4">
                <div class="services-2 text-center">
                    <div class="icon">
                        <span class="flaticon-light-bulb"></span>
                    </div>
                    <div class="text">
                        <h3>Electricity Bill Payment</h3>
                        <p>Pay your electricity bills securely and promptly, ensuring uninterrupted power supply.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="ftco-counter img" id="section-counter">
    <div class="container">
        <div class="row no-gutters d-flex">
            <div class="col-md-6 d-flex">
                <div class="img d-flex align-self-stretch" style="background-image:url(images/APAY.png);"></div>
            </div>
            <div class="col-md-6 p-3 pl-md-5 py-5" style="background-color: green;">
                <div class="row justify-content-start pb-3">
                    <div class="col-md-12 heading-section heading-section-white ftco-animate">
                        <h2 class="mb-4">Why Choose <span>A-Pay?</span></h2>
                        <p>Seamless and secure transactions for Airtime, Data, and Online Payments.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 justify-content-center counter-wrap ftco-animate">
                        <div class="block-18 mb-4">
                            <div class="text">
                                <strong class="number" data-number="10000">0</strong>
                                <span>Successful Transactions</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 justify-content-center counter-wrap ftco-animate">
                        <div class="block-18 mb-4">
                            <div class="text">
                                <strong class="number" data-number="8500">0</strong>
                                <span>Happy Customers</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 justify-content-center counter-wrap ftco-animate">
                        <div class="block-18 mb-4">
                            <div class="text">
                                <strong class="number" data-number="1200">0</strong>
                                <span>Vendors & Partners</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 justify-content-center counter-wrap ftco-animate">
                        <div class="block-18 mb-4">
                            <div class="text">
                                <strong class="number" data-number="99">0</strong>
                                <span>% Uptime & Reliability</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ftco-section services-section">
    <div class="container">
        <div class="row justify-content-center pb-5">
            <div class="col-md-6 heading-section text-center ftco-animate">
                <h2 class="mb-4">Our Exclusive <span style="color: green;">Services</span></h2>
            </div>
        </div>
        <div class="row d-flex no-gutters">
            <div class="col-md-3 d-flex align-self-stretch ftco-animate">
                <div class="media block-6 services d-block">
                    <div class="line"></div>
                    <div class="icon"><span class="flaticon-smartphone"><i class="fas fa-mobile-alt"></i></span></div>
                    <div class="media-body">
                        <h3 class="heading mb-3">Airtime Recharge</h3>
                        <p>Instantly top up your mobile airtime for all major networks with ease.</p>
                    </div>
                </div>      
            </div>
            <div class="col-md-3 d-flex align-self-stretch ftco-animate">
                <div class="media block-6 services d-block">
                    <div class="line"></div>
                    <div class="icon"><span class="flaticon-wifi"><i class="fas fa-wifi"></i></span></div>
                    <div class="media-body">
                        <h3 class="heading mb-3">Data Subscription</h3>
                        <p>Buy affordable and fast data plans for all networks at the best rates.</p>
                    </div>
                </div>    
            </div>
            <div class="col-md-3 d-flex align-self-stretch ftco-animate">
                <div class="media block-6 services d-block">
                    <div class="line"></div>
                    <div class="icon"><span class="flaticon-light-bulb"></span></div>
                    <div class="media-body">
                        <h3 class="heading mb-3">Electricity Bills</h3>
                        <p>Pay your electricity bills easily and keep the lights on without hassle.</p>
                    </div>
                </div>      
            </div>
            <div class="col-md-3 d-flex align-self-stretch ftco-animate">
                <div class="media block-6 services d-block">
                    <div class="line"></div>
                    <div class="icon"><span class="flaticon-credit-card"><i class="fas fa-credit-card"></i></span></div>
                    <div class="media-body">
                        <h3 class="heading mb-3">Seamless Payments</h3>
                        <p>Fast, secure, and convenient payments for all your essential services.</p>
                    </div>
                </div>      
            </div>
        </div>
    </div>
</section>

<section class="ftco-section bg-light ftco-faqs">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 order-md-last">
                <div class="img img-video d-flex align-self-stretch align-items-center justify-content-center justify-content-md-center mb-4 mb-sm-0" style="background-image:url(https://img.freepik.com/free-photo/doubtful-indecisive-woman-raises-palm-with-hesitation-faces-difficult-question-two-choices-wears-orange-sweater-earrings-isolated-green-wall-people-perception-attitude_273609-39478.jpg?uid=R113681707&ga=GA1.1.1286231835.1732289670&semt=ais_hybrid);">
                </div>
             <!--    <div class="d-flex mt-3">
                    <div class="img img-2 mr-md-2 w-100" style="background-image:url(https://img.freepik.com/free-photo/doubtful-indecisive-woman-raises-palm-with-hesitation-faces-difficult-question-two-choices-wears-orange-sweater-earrings-isolated-green-wall-people-perception-attitude_273609-39478.jpg?uid=R113681707&ga=GA1.1.1286231835.1732289670&semt=ais_hybrid);"></div>
                    <div class="img img-2 ml-md-2 w-100" style="background-image:url(https://img.freepik.com/free-photo/positive-cheerful-dark-skinned-curly-woman-shapes-something-very-little-with-fingers-demonstrates-small-decreased-price-salary-gestures-big-object-smiles-toothily-green-color-prevails_273609-38271.jpg?uid=R113681707&ga=GA1.1.1286231835.1732289670&semt=ais_hybrid);"></div>
                </div> -->
            </div>

            <div class="col-lg-6">
                <div class="heading-section mb-5 mt-5 mt-lg-0">
                    <h2 class="mb-3">Frequently Asked Questions</h2>
                </div>
                <div id="accordion" class="myaccordion w-100" aria-multiselectable="true">
                    <div class="card">
                        <div class="card-header p-0" id="headingOne">
                            <h2 class="mb-0">
                                <button href="#collapseOne" class="d-flex py-3 px-4 align-items-center justify-content-between btn btn-link" data-parent="#accordion" data-toggle="collapse" aria-expanded="true" aria-controls="collapseOne">
                                    <p class="mb-0">What is A-Pay?</p>
                                    <i class="fa" aria-hidden="true"></i>
                                </button>
                            </h2>
                        </div>
                        <div class="collapse show" id="collapseOne" role="tabpanel" aria-labelledby="headingOne">
                            <div class="card-body py-3 px-0">
                                <p>A-Pay is a secure and fast payment service that allows users to:</p>
                                <ol>
                                    <li>Buy airtime instantly</li>
                                    <li>Purchase mobile data</li>
                                    <li>Pay electricity bills seamlessly</li>
                                    <li>Topup your betting account's seamlessly</li>
                                    <li>Enjoy a smooth and reliable payment experience</li>
                                    <li>Access 24/7 customer support</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-0" id="headingTwo" role="tab">
                            <h2 class="mb-0">
                                <button href="#collapseTwo" class="d-flex py-3 px-4 align-items-center justify-content-between btn btn-link" data-parent="#accordion" data-toggle="collapse" aria-expanded="false" aria-controls="collapseTwo">
                                    <p class="mb-0">How do I use A-Pay?</p>
                                    <i class="fa" aria-hidden="true"></i>
                                </button>
                            </h2>
                        </div>
                        <div class="collapse" id="collapseTwo" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="card-body py-3 px-0">
                                <ol>
                                    <li>Save the A-Pay WhatsApp number and send "Hi" to start.</li>
                                    <li>Create your A-Pay account or log in directly inside WhatsApp.</li>
                                    <li>Top up your A-Pay wallet through the chat.</li>
                                    <li>Select the service you want: airtime, data, or bill payment.</li>
                                    <li>Provide the required details (phone number, network, amount, meter number).</li>
                                    <li>Confirm your transaction and receive instant WhatsApp notification once successful.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-0" id="headingThree" role="tab">
                            <h2 class="mb-0">
                                <button href="#collapseThree" class="d-flex py-3 px-4 align-items-center justify-content-between btn btn-link" data-parent="#accordion" data-toggle="collapse" aria-expanded="false" aria-controls="collapseThree">
                                    <p class="mb-0">What payment methods are accepted?</p>
                                    <i class="fa" aria-hidden="true"></i>
                                </button>
                            </h2>
                        </div>
                        <div class="collapse" id="collapseThree" role="tabpanel" aria-labelledby="headingThree">
                            <div class="card-body py-3 px-0">
                                <ol>
                                    <li>Debit and credit cards</li>
                                    <li>Mobile money services</li>
                                    <li>Bank transfers</li>
                                    <li>Wallet payments</li>
                                    <li>Other local payment options</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-0" id="headingFour" role="tab">
                            <h2 class="mb-0">
                                <button href="#collapseFour" class="d-flex py-3 px-4 align-items-center justify-content-between btn btn-link" data-parent="#accordion" data-toggle="collapse" aria-expanded="false" aria-controls="collapseFour">
                                    <p class="mb-0">Is A-Pay safe and secure?</p>
                                    <i class="fa" aria-hidden="true"></i>
                                </button>
                            </h2>
                        </div>
                        <div class="collapse" id="collapseFour" role="tabpanel" aria-labelledby="headingFour">
                            <div class="card-body py-3 px-0">
                                <p>Yes, A-Pay ensures maximum security by:</p>
                                    <ol>
                                        <li>Your WhatsApp transactions are fully encrypted for maximum safety.</li>
                                        <li>Login on WhatsApp is protected with secure verification steps.</li>
                                        <li>A-Pay connects only with trusted and verified payment partners.</li>
                                        <li>Smart fraud checks run automatically to keep your WhatsApp account safe.</li>
                                        <li>24/7 monitoring ensures every WhatsApp transaction stays protected.</li>
                                    </ol>
                            </div>
                        </div>
                    </div>

                        <div class="card">
                            <div class="card-header p-0" id="headingFive" role="tab">
                                <h2 class="mb-0">
                                    <button href="#collapseFive" class="d-flex py-3 px-4 align-items-center justify-content-between btn btn-link" data-parent="#accordion" data-toggle="collapse" aria-expanded="false" aria-controls="collapseFive">
                                        <p class="mb-0">Lost Your Phone? Secure Your A-Pay Instantly</p>
                                        <i class="fa" aria-hidden="true"></i>
                                    </button>
                                </h2>
                            </div>
                            <div class="collapse" id="collapseFive" role="tabpanel" aria-labelledby="headingFive">
                                <div class="card-body py-3 px-0">
                                    <ol>
                                        <li>Click the button below to immediately block your A-Pay account and prevent unauthorized access:</li>
                                        <li>
                                            <a href="{{ url('block-account') }}" 
                                               class="btn btn-danger d-flex align-items-center px-4 py-3 mt-2" 
                                               role="button">
                                                <i class="fab fa-block fa-2x me-2"></i>
                                                <span class="fw-bold" style="color: white;">Block Account</span>
                                            </a>
                                        </li>
                                        <li>If you want to reopen your account later, send a message to our customer care on WhatsApp at <strong>09079916807</strong> and we will assist you in restoring access securely.</li>
                                    </ol>

                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</section>
    

<section class="ftco-section ftco-no-pb testimony-section" style="background-image: url(https://img.freepik.com/free-photo/woman-with-headset-laptop-working-from-home_23-2148708942.jpg?t=st=1741810864~exp=1741814464~hmac=d1310faaa6a68ab7d336031e67dc443493c18f85aef189d1813e56f80ca66f7a&w=900);">
    <div class="overlay-1"></div>
    <div class="container-fluid">
        <div class="row justify-content-center mb-5 pb-3">
            <div class="col-md-7 text-center heading-section heading-section-white ftco-animate">
                <h2 class="mb-4">What Our Customers Say</h2>
            </div>
        </div>
        <div class="row ftco-animate">
            <div class="col-md-12 testimonial">
                <div class="carousel-testimony owl-carousel ftco-owl">
                    <div class="item">
                        <div class="testimony-wrap d-flex align-items-stretch" style="background-image: url(https://source.unsplash.com/600x600/?business,man);">
                            <div class="overlay"></div>
                            <div class="text">
                                <div class="line"></div>
                                <p class="mb-4">A-Pay made my life easier! Now I can buy airtime and pay my bills in seconds.</p>
                                <p class="name">Joshua Adeyemi</p>
                            </div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="testimony-wrap d-flex align-items-stretch" style="background-image: url(https://source.unsplash.com/600x600/?woman,technology);">
                            <div class="overlay"></div>
                            <div class="text">
                                <div class="line"></div>
                                <p class="mb-4">Super fast and reliable! A-Pay is my go-to platform for all my transactions.</p>
                                <p class="name">Friday Anaja.</p>
                            </div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="testimony-wrap d-flex align-items-stretch" style="background-image: url(https://source.unsplash.com/600x600/?man,finance);">
                            <div class="overlay"></div>
                            <div class="text">
                                <div class="line"></div>
                                <p class="mb-4">No more long queues! A-Pay has transformed how I pay for utilities.</p>
                                <p class="name">Michael Adesina.</p>
                            </div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="testimony-wrap d-flex align-items-stretch" style="background-image: url(https://source.unsplash.com/600x600/?woman,success);">
                            <div class="overlay"></div>
                            <div class="text">
                                <div class="line"></div>
                                <p class="mb-4">The best payment platform I’ve ever used. Highly recommend A-Pay!</p>
                                <p class="name">Dele Adegbande</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!--  <section class="ftco-section bg-light">
    <div class="container">
        <div class="row justify-content-center mb-5 pb-3">
            <div class="col-md-7 heading-section text-center ftco-animate">
                <h2>Explore Our Latest Insights</h2>
            </div>
        </div>
        <div class="row d-flex">
            <div class="col-md-4 d-flex ftco-animate">
                <div class="blog-entry justify-content-end">
                    <a href="#" class="block-20" style="background-image: url('https://img.freepik.com/free-photo/black-businessman-using-computer-laptop_53876-14801.jpg?uid=R113681707&ga=GA1.1.1286231835.1732289670&semt=ais_authors_boost');">
                    </a>
                    <div class="text mt-3 float-right d-block">
                        <div class="d-flex align-items-center pt-2 mb-4 topp">
                            <div class="one">
                                <span class="day">12</span>
                            </div>
                            <div class="two pl-1">
                                <span class="yr">2025</span>
                                <span class="mos">March</span>
                            </div>
                        </div>
                        <h3 class="heading"><a href="#">How A-Pay Simplifies Your Daily Transactions</a></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-flex ftco-animate">
                <div class="blog-entry justify-content-end">
                    <a href="#" class="block-20" style="background-image: url('https://img.freepik.com/free-photo/close-up-portrait-attractive-young-woman-isolated_273609-35666.jpg?uid=R113681707&ga=GA1.1.1286231835.1732289670&semt=ais_authors_boost');">
                    </a>
                    <div class="text mt-3 float-right d-block">
                        <div class="d-flex align-items-center pt-2 mb-4 topp">
                            <div class="one">
                                <span class="day">05</span>
                            </div>
                            <div class="two pl-1">
                                <span class="yr">2025</span>
                                <span class="mos">March</span>
                            </div>
                        </div>
                        <h3 class="heading"><a href="#">The Future of Mobile Payments with A-Pay</a></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-flex ftco-animate">
                <div class="blog-entry">
                    <a href="#" class="block-20" style="background-image: url('https://img.freepik.com/free-photo/credit-card-finance-held-by-hand-banking-campaign_53876-129578.jpg?t=st=1741398567~exp=1741402167~hmac=da26125d65898154669604c3da5a8355a19b31a7f1b8a4c6fc6aa7972c5befae&w=1060');">
                    </a>
                    <div class="text mt-3 float-right d-block">
                        <div class="d-flex align-items-center pt-2 mb-4 topp">
                            <div class="one">
                                <span class="day">27</span>
                            </div>
                            <div class="two pl-1">
                                <span class="yr">2025</span>
                                <span class="mos">February</span>
                            </div>
                        </div>
                        <h3 class="heading"><a href="#">Why Digital Transactions Are the Future</a></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
 -->
<section class="apy-security-section py-5" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8 mx-auto text-center">
                <h2 class="mb-3 text-dark">Lost Your Phone? Secure Your A-Pay Instantly</h2>
                <p class="mb-4 text-secondary">If your device is lost or stolen, don’t worry — you can freeze your A-Pay account directly from any WhatsApp device. We’ll stop all transactions and guide you step-by-step to safely recover your account.</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center">
                    <!-- Block Account Button -->
                    <a href="{{ url('block-account') }}" 
                       class="btn btn-danger d-flex align-items-center px-4 py-3" 
                       role="button">
                        <i class="fas fa-lock fa-2x me-2"></i>
                        <span class="fw-bold">Block Account Now</span>
                    </a>

                    <!-- Unblock Account Button -->
                    <a href="https://wa.me/09079916807?text=UNBLOCK" 
                       class="btn btn-success d-flex align-items-center px-4 py-3" 
                       role="button">
                        <i class="fab fa-whatsapp fa-2x me-2"></i>
                        <span class="fw-bold">Unblock Account Now</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


    <section class="ftco-appointment ftco-section img" style="background-image: url(https://images.unsplash.com/photo-1596524430615-b46475ddff6e?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D);">
        <div class="overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-6 half ftco-animate">
                    <h2 class="mb-4">Don't hesitate to contact us</h2>
                    <form  id="contactUsForm" action="#" class="appointment">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" id="email" name="email" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input type="number" class="form-control" id="phone" name="phone_number" placeholder="Phone Number" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="form-field">
                                        <div class="select-wrap">
                                            <div class="icon"><span class="fa fa-chevron-down"></span></div>
                                            <select id="category" name="category" class="form-control" required>
                                                  <option value="">Select a category</option>
                                                  <option value="General Inquiry">General Inquiry</option>
                                                  <option value="Support">Support</option>
                                                  <option value="Feedback">Feedback</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <textarea id="complaint" name="complaint" cols="30" rows="7" class="form-control" placeholder="Message" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" value="Send message" class="btn btn-primary py-3 px-4">Send Message</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

  
  @include('components.footer')