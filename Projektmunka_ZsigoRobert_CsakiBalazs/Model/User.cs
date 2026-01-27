using System.ComponentModel.DataAnnotations;

namespace StockMaster.Api.Models
{
    public class User
    {
        public int Id { get; set; }
        [Required] public string Email { get; set; }
        [Required] public string PasswordHash { get; set; }
        public decimal Balance { get; set; } = 10000m;
        public bool KeepLoggedIn { get; set; } = false;
    }
}
