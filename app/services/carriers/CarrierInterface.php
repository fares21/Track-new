<?php
namespace App\services\carriers;

interface CarrierInterface {
    // محاولة اكتشاف الناقل من الرقم، تُرجع carrier_code أو null
    public function detect(string $trackingNumber): ?string;

    // جلب البيانات الخام من API أو مصدر
    public function fetch(string $trackingNumber, ?string $carrierCode = null): array;

    // تحويل الاستجابة إلى هيكل موحد
    public function parseResponse(array $raw): array;

    // اسم مزود العرض الأصلي
    public function providerUrl(string $trackingNumber, ?string $carrierCode = null): ?string;
}
