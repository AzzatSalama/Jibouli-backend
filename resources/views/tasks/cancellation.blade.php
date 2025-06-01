<div class="bg-white rounded-lg shadow-md p-6 mb-4 border-l-4 border-red-500">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">📦 Suivi d'annulation de commande</h2>
            <p class="text-sm text-gray-500 mt-1">ID de commande: #{{ $order->id }}</p>
        </div>
        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Urgent</span>
    </div>

    <div class="mb-6">
        <h3 class="font-medium text-gray-700 mb-2">📝 Description de la tâche</h3>
        <p class="text-gray-600 text-sm">
            Contacter le client pour comprendre la raison de l'annulation et tenter de résoudre les problèmes.
        </p>
    </div>

    <div class="mb-6">
        <h3 class="font-medium text-gray-700 mb-2">💬 Message suggéré</h3>
        <div class="bg-gray-50 rounded-lg p-4 relative">
            <p class="text-gray-600 text-sm mb-3" id="messageText" dir="rtl" style="text-align: right;">
                "مرحبا {{ $order->client->client_name }}، لاحظنا إلغاء طلبك الأخير (رقم {{ $order->id }}).
                هل يمكنك مشاركة ملاحظاتك حول سبب إلغاء طلبك لمساعدتنا على التحسين؟!"
            </p>
            <button onclick="copyToClipboard()" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Copier le message
            </button>
        </div>
    </div>

    <div class="border-t pt-4">
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->client->client_phone) }}?text={{ urlencode("مرحبا " . $order->client->client_name . "، بخصوص الطلب الملغي رقم " . $order->id . "، هل يمكنك مشاركة ملاحظاتك حول سبب إلغاء طلبك لمساعدتنا على التحسين؟") }}"
            target="_blank"
            class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884" />
            </svg>
            Contacter via WhatsApp
        </a>
    </div>

    <script>
        function copyToClipboard() {
            const text = document.getElementById("messageText").innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert("Message copié dans le presse-papiers !");
            });
        }
    </script>
</div>