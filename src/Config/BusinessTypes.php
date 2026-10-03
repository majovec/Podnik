<?php
namespace App\Config;

final class BusinessTypes {
    public static function all(): array {
        return [
            'services'=>['name'=>'Služby a řemesla','short'=>'Služby','image'=>'services.svg','color'=>'blue','modules'=>['Zakázky','Fakturace','Kalendář','Banka'],'intro'=>'Zakázky, práce, fakturace a cash-flow na jednom místě.'],
            'hair_beauty'=>['name'=>'Kadeřnictví a krása','short'=>'Kadeřnictví','image'=>'hair-beauty.svg','color'=>'violet','modules'=>['Kalendář','CRM','Fakturace','Sklad'],'intro'=>'Přehled klientů, termínů, tržeb a spotřeby materiálu.'],
            'wellness'=>['name'=>'Wellness, sauna a relax','short'=>'Wellness','image'=>'wellness.svg','color'=>'cyan','modules'=>['Kalendář','CRM','Fakturace','Sklad'],'intro'=>'Obsazenost, klienti, služby, tržby a zásoby bez zbytečného přepínání.'],
            'fitness'=>['name'=>'Fitness a sport','short'=>'Fitness','image'=>'fitness.svg','color'=>'green','modules'=>['Kalendář','CRM','Fakturace','Zakázky'],'intro'=>'Klienti, termíny, služby, tržby a provozní přehled.'],
            'restaurant'=>['name'=>'Restaurace a gastro','short'=>'Gastro','image'=>'restaurant.svg','color'=>'orange','modules'=>['Sklad','Dodavatelé','Fakturace','Banka'],'intro'=>'Tržby, náklady, zásoby a závazky v jednom přehledu.'],
            'ecommerce'=>['name'=>'E-shop','short'=>'E-shop','image'=>'ecommerce.svg','color'=>'blue','modules'=>['Sklad','Banka','Fakturace','CRM'],'intro'=>'Objednávky, zásoby, peníze a faktury s důrazem na provoz.'],
            'retail'=>['name'=>'Obchod','short'=>'Obchod','image'=>'retail.svg','color'=>'violet','modules'=>['Sklad','Banka','Fakturace','CRM'],'intro'=>'Zásoby, prodej, dodavatelé a finanční přehled na jednom místě.'],
            'production'=>['name'=>'Výroba','short'=>'Výroba','image'=>'production.svg','color'=>'slate','modules'=>['Sklad','Zakázky','Dodavatelé','Banka'],'intro'=>'Zakázky, materiál, sklad a peněžní tok pro výrobu.'],
            'consulting'=>['name'=>'Konzultace a kancelář','short'=>'Konzultace','image'=>'consulting.svg','color'=>'cyan','modules'=>['CRM','Zakázky','Fakturace','Kalendář'],'intro'=>'Klienti, projekty, termíny a fakturace bez administrativního balastu.'],
            'other'=>['name'=>'Jiný typ podnikání','short'=>'Jiné','image'=>'other.svg','color'=>'slate','modules'=>['Fakturace','Banka','CRM','Sklad'],'intro'=>'Byznio přizpůsobí přehled dostupným datům vašeho podnikání.'],
        ];
    }
    public static function get(?string $key): array {
        $all=self::all();
        return $all[$key]??$all['other'];
    }
}
