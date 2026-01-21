using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using StockMaster.Wpf.Services;
using System.Collections.ObjectModel;

namespace StockMaster.Wpf.ViewModels
{
    public class MainViewModel : ObservableObject
    {
        private readonly ApiClient _api;
        public ObservableCollection<PositionViewModel> Positions { get; } = new();
        public ObservableCollection<Tutorial> Tutorials { get; } = new();

        public MainViewModel(ApiClient api)
        {
            _api = api;
            SetTimeframeCommand = new RelayCommand<string>(OnSetTimeframe);
            BuyCommand = new AsyncRelayCommand(OnBuyAsync);
            SellCommand = new AsyncRelayCommand(OnSellAsync);
            OpenTutorialCommand = new RelayCommand(OnOpenTutorial);
            // load initial data
            Task.Run(LoadInitial);
        }

        public IRelayCommand<string> SetTimeframeCommand { get; }
        public IAsyncRelayCommand BuyCommand { get; }
        public IAsyncRelayCommand SellCommand { get; }
        public IRelayCommand OpenTutorialCommand { get; }

        // Bindable props
        string _selectedAssetDisplay = "AAPL - Apple Inc.";
        public string SelectedAssetDisplay { get => _selectedAssetDisplay; set => SetProperty(ref _selectedAssetDisplay, value); }

        decimal _buyerRatio = 60;
        public double BuyerRatioPercent { get => (double)_buyerRatio; set => SetProperty(ref _buyerRatio, (decimal)value); }

        public string TradeQuantity { get; set; } = "1";

        private async Task LoadInitial()
        {
            // load portfolio, assets, tutorials via _api
        }

        private void OnSetTimeframe(string tf)
        {
            // switch timeframe (1m,5m,...)
        }

        private async Task OnBuyAsync()
        {
            // call API to open position -> update Positions[]
        }

        private async Task OnSellAsync()
        {
            // close or open short
        }

        private void OnOpenTutorial()
        {
            // open PDF via default system viewer
        }
    }
}
