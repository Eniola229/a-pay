@include('components.header-guest')

<section class="hero-wrap hero-wrap-2" style="background-image: url('https://images.unsplash.com/photo-1499750310107-5fef28a66643?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');" data-stellar-background-ratio="0.5">
  <div class="overlay"></div>
  <div class="container">
    <div class="row no-gutters slider-text align-items-end justify-content-center">
      <div class="col-md-9 ftco-animate pb-5 text-center">
        <h1 class="mb-3 bread">Read our blog</h1>
        <p class="breadcrumbs"><span class="mr-2"><a href="{{ url('/') }}">Home <i class="fa fa-chevron-right"></i></a></span> <span>Blog <i class="fa fa-chevron-right"></i></span></p>
      </div>
    </div>
  </div>
</section>

<section class="ftco-section bg-light">
  <div class="container">
    <div class="row justify-content-center mb-5 pb-3">
      <div class="col-md-7 heading-section text-center ftco-animate">
        <h2>Latest Updates from A-Pay</h2>
      </div>
    </div>
    <div class="row d-flex">
      <div class="col-md-4 d-flex ftco-animate">
        <div class="blog-entry justify-content-end">
          <a href="#" class="block-20" style="background-image: url('https://img.freepik.com/free-photo/3d-rendering-smartphone-with-money-transfer-application_107791-15399.jpg');">
          </a>
          <div class="text mt-3 float-right d-block">
            <div class="d-flex align-items-center pt-2 mb-4 topp">
              <div class="one"><span class="day">15</span></div>
              <div class="two pl-1"><span class="yr">2025</span> <span class="mos">March</span></div>
            </div>
            <h3 class="heading"><a href="#">How A-Pay is Simplifying Online Payments</a></h3>
          </div>
        </div>
      </div>
      
      <div class="col-md-4 d-flex ftco-animate">
        <div class="blog-entry justify-content-end">
          <a href="#" class="block-20" style="background-image: url('https://images.unsplash.com/photo-1655036387197-566206c80980?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');">
          </a>
          <div class="text mt-3 float-right d-block">
            <div class="d-flex align-items-center pt-2 mb-4 topp">
              <div class="one"><span class="day">10</span></div>
              <div class="two pl-1"><span class="yr">2025</span> <span class="mos">March</span></div>
            </div>
            <h3 class="heading"><a href="#">Secure and Fast: A-Pay's Latest Security Features</a></h3>
          </div>
        </div>
      </div>

      <div class="col-md-4 d-flex ftco-animate">
        <div class="blog-entry">
          <a href="#" class="block-20" style="background-image: url('https://img.freepik.com/free-photo/person-paying-using-nfc-technology_23-2149893725.jpg?uid=R113681707&ga=GA1.1.1286231835.1732289670&semt=ais_authors_boost');">
          </a>
          <div class="text mt-3 float-right d-block">
            <div class="d-flex align-items-center pt-2 mb-4 topp">
              <div class="one"><span class="day">5</span></div>
              <div class="two pl-1"><span class="yr">2025</span> <span class="mos">March</span></div>
            </div>
            <h3 class="heading"><a href="#">Top 5 Benefits of Using A-Pay for Daily Transactions</a></h3>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row mt-5">
      <div class="col text-center">
        <div class="block-27">
          <ul>
            <li><a href="#">&lt;</a></li>
            <li class="active"><span>1</span></li>
            <li><a href="#">&gt;</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

@include('components.footer')