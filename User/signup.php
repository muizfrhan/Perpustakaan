<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../Assets/css/signupuser.css" />
  </head>
  <body>
    <div class="signup-container">
      <!-- Sign Up Header -->
      <h2 class="signup-header mt-4 text-center">Sign Up</h2>
      <p class="login-subtitle mb-4 text-center">
        Register your account to explore
      </p>
      <!-- Sign-Up Form -->
      <form>
        <!-- Full Name -->
        <div class="mb-3">
          <label for="fullname" class="form-label">Fullname</label>
          <input
            type="text"
            class="form-control"
            id="fullname"
            placeholder="Fullname"
            required
          />
        </div>

        <!-- Gender -->
        <div class="mb-3">
          <label class="form-label d-block">Gender</label>
          <div class="form-check form-check-inline">
            <input
              class="form-check-input"
              type="radio"
              name="gender"
              id="male"
              value="Male"
              required
            />
            <label class="form-check-label" for="male">Male</label>
          </div>
          <div class="form-check form-check-inline">
            <input
              class="form-check-input"
              type="radio"
              name="gender"
              id="female"
              value="Female"
              required
            />
            <label class="form-check-label" for="female">Female</label>
          </div>
        </div>

        <!-- NIM -->
        <div class="mb-3">
          <label for="nim" class="form-label">NIM</label>
          <input
            type="text"
            class="form-control"
            id="nim"
            placeholder="Enter your NIM"
            required
          />
        </div>

        <!-- Class -->
        <div class="mb-3">
          <label for="class" class="form-label">Class</label>
          <input
            type="text"
            class="form-control"
            id="class"
            placeholder="Enter your class (e.g., IF-2A)"
            required
          />
        </div>

        <!-- Major -->
        <div class="mb-3">
          <label for="major" class="form-label">Major</label>
          <select class="form-select" id="major" required>
            <option value="" selected disabled>Select your major</option>
            <option value="Informatics">Informatics</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Information Systems">Information Systems</option>
            <option value="Cybersecurity">Cybersecurity</option>
          </select>
        </div>

        <!-- Date of Birth -->
        <div class="mb-3">
          <label for="dob" class="form-label">Date of Birth</label>
          <input type="date" class="form-control" id="dob" required />
        </div>

        <!-- Create Password -->
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input
              type="password"
              class="form-control"
              id="password"
              placeholder="Create Password"
              required
            />
            <button
              class="btn btn-outline-warning"
              type="button"
              id="togglePassword"
            >
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Retype Password -->
        <div class="mb-3">
          <div class="input-group">
            <input
              type="password"
              class="form-control"
              id="confirmPassword"
              placeholder="Retype Password"
              required
            />
            <button
              class="btn btn-outline-warning"
              type="button"
              id="toggleConfirmPassword"
            >
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
      </form>
    </div>

    <script>
      // Toggle password visibility
      const togglePassword = document.getElementById("togglePassword");
      const passwordInput = document.getElementById("password");

      const toggleConfirmPassword = document.getElementById(
        "toggleConfirmPassword"
      );
      const confirmPasswordInput = document.getElementById("confirmPassword");

      togglePassword.addEventListener("click", () => {
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          togglePassword.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
          passwordInput.type = "password";
          togglePassword.innerHTML = '<i class="fas fa-eye"></i>';
        }
      });

      toggleConfirmPassword.addEventListener("click", () => {
        if (confirmPasswordInput.type === "password") {
          confirmPasswordInput.type = "text";
          toggleConfirmPassword.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
          confirmPasswordInput.type = "password";
          toggleConfirmPassword.innerHTML = '<i class="fas fa-eye"></i>';
        }
      });
    </script>
  </body>
</html>
