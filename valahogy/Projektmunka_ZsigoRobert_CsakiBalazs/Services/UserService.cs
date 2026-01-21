using Microsoft.Extensions.Configuration;
using Microsoft.IdentityModel.Tokens;
using StockMaster.Api.Data;
using StockMaster.Api.Models;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;
using Microsoft.EntityFrameworkCore;
using System.Security.Cryptography;

namespace StockMaster.Api.Services
{
    public class UserService : IUserService
    {
        private readonly ApplicationDbContext _db;
        private readonly IConfiguration _config;

        public UserService(ApplicationDbContext db, IConfiguration config)
        {
            _db = db;
            _config = config;
        }

        public async Task<User> RegisterAsync(string email, string password, decimal initialBalance = 10000m)
        {
            if (await _db.Users.AnyAsync(u => u.Email == email))
                throw new Exception("User exists");

            var user = new User
            {
                Email = email,
                PasswordHash = HashPassword(password),
                Balance = initialBalance
            };
            _db.Users.Add(user);
            await _db.SaveChangesAsync();
            return user;
        }

        public async Task<User?> AuthenticateAsync(string email, string password)
        {
            var user = await _db.Users.SingleOrDefaultAsync(u => u.Email == email);
            if (user == null) return null;
            if (!VerifyPassword(password, user.PasswordHash)) return null;
            return user;
        }

        public string GenerateJwtToken(User user)
        {
            var jwt = _config.GetSection("Jwt");
            var key = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwt["Key"]));
            var creds = new SigningCredentials(key, SecurityAlgorithms.HmacSha256);

            var claims = new[]
            {
                new Claim(JwtRegisteredClaimNames.Sub, user.Email),
                new Claim("id", user.Id.ToString())
            };

            var token = new JwtSecurityToken(
                issuer: jwt["Issuer"],
                audience: jwt["Audience"],
                claims: claims,
                expires: DateTime.UtcNow.AddMinutes(double.Parse(jwt["ExpireMinutes"])),
                signingCredentials: creds
            );
            return new JwtSecurityTokenHandler().WriteToken(token);
        }

        // VERY simple password hashing (for demo only) — replace with real hashing (e.g. PBKDF2)
        private string HashPassword(string password)
        {
            using var sha = SHA256.Create();
            var hash = sha.ComputeHash(Encoding.UTF8.GetBytes(password));
            return Convert.ToBase64String(hash);
        }
        private bool VerifyPassword(string password, string storedHash)
        {
            return HashPassword(password) == storedHash;
        }
    }
}
