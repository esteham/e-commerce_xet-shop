</div> <!-- row div -->
</div> <!-- container-fluid div -->



<footer class="footer">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-left">
        <span class="d-inline-block mr-2">&copy; <span id="year"></span> SpiDer Monkey Admin</span>
        <span class="badge badge-pill badge-primary">v1.0.0</span>
      </div>
      <div class="col-md-6 text-center text-md-right mt-2 mt-md-0">
        <span class="d-inline-block mr-3">
          <i class="fas fa-server mr-1"></i> Status: <span class="text-success">Online</span>
        </span>
        <span class="d-inline-block">
          <i class="fas fa-clock mr-1"></i> <span id="datetime"></span>
        </span>
      </div>
    </div>
  </div>
</footer>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.1/js/bootstrap.bundle.min.js" integrity="sha512-mULnawDVcCnsk9a4aG1QLZZ6rcce/jSzEGqUkeOLy0b6q0+T6syHrxlsAGH7ZVoqC93Pd0lBqd6WguPWih7VHA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Footer Scripts -->
<script>
  // Set current year
  document.getElementById('year').textContent = new Date().getFullYear();
  
  // Update datetime every second
  function updateDateTime() {
    const now = new Date();
    const options = { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    };
    document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
  }
  
  setInterval(updateDateTime, 1000);
  updateDateTime(); // Initial call
</script>
</body>
</html>
