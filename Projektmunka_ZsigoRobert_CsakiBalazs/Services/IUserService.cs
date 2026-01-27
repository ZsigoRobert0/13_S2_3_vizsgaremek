using StockMaster.Api.Models;

namespace StockMaster.Api.Services
{
    public interface IUserService
    {
        Task<User?> AuthenticateAsync(string email, string password);
        Task<User> RegisterAsync(string email, string password, decimal initialBalance = 10000m);
        string GenerateJwtToken(User user);
    }
}
