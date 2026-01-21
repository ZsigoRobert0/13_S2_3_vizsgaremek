using Microsoft.AspNetCore.Mvc;
using StockMaster.Api.Services;
using StockMaster.Api.DTOs;

namespace StockMaster.Api.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    public class AuthController : ControllerBase
    {
        private readonly IUserService _users;
        public AuthController(IUserService users) => _users = users;

        [HttpPost("register")]
        public async Task<IActionResult> Register(RegisterDto dto)
        {
            try
            {
                var user = await _users.RegisterAsync(dto.Email, dto.Password, dto.InitialBalance);
                var token = _users.GenerateJwtToken(user);
                return Ok(new { token });
            }
            catch (Exception ex)
            {
                return BadRequest(new { error = ex.Message });
            }
        }

        [HttpPost("login")]
        public async Task<IActionResult> Login(LoginDto dto)
        {
            var user = await _users.AuthenticateAsync(dto.Email, dto.Password);
            if (user == null) return Unauthorized();
            var token = _users.GenerateJwtToken(user);
            return Ok(new { token });
        }
    }
}
