# CRM pro Nette Framework
Administrace webu

## Nastaveni
Zaregistrujte a nastavete extension v **config.neon**. Od namespace je odvozen název modulu pro další rozšíření administrace a to tak, že se k namespace přidá '*Ext*'
```neon
extensions:
    crm: NAttreid\Crm\DI\CrmExtension

crm:
    namespace: 'Crm'
    url: '/crm/'
    secured: false
    sender: 'Odesilatel <nejaky@mail.cz>'

    fileManagerDir: %appDir%/../
    infoRefresh: 15 # vteriny

    minPasswordLength: 8
    passwordChars: '0-9a-zA-Z'

    loginExpiration: '20 minutes'
    sessionExpiration: '14 days'

    layout: '%appDir%/modules/CrmExt/templates/layout.latte' # hlavní šablona
```

a přidejte model do ORM. V příkladu je extension orm pod nazvem **orm**
```neon
orm:
    add:
        - NAttreid\Crm\Model\Orm
```

Přídání dalších modulů
```neon
crm:
    menu:
        Example:
            link: 'Homepage:'
            web:
                test:
                    link: '' # pokud je prazdny, provede se default action
```
Presenter musí dědit z třídy **\NAttreid\Crm\Control\Presenter**. Příklad presenteru test z ukázky menu
```php
namespace App\CrmExtModule\ExampleModule\Presenters;

class TestPresenter extends \NAttreid\Crm\Control\Presenter {
    public function renderDefault() {
        // pro zobrazeni menu v mobilu (defaultne je skryto)
        $this->viewMobileMenu();
    }
}
```

**Layout.latte**
```latte
{extends $layout}
```

## Rozšiřitelnost pomocí extension
Třída extension musí dědit z **\NAttreid\Crm\DI\ModuleExtension**
```php
class ExampleExtension extends \NAttreid\Crm\DI\ModuleExtension {

    protected $namespace = 'example';
    protected $dir = __DIR__;
    protected $package = 'Package\\';

    public function beforeCompile() {
        parent::beforeCompile();
        $this->addLoaderFile('cestaKCssNeboJs');
        $this->addLoaderFile('cestaKLocalizovanemuJs','cs');
    }

}
```

a v složce musí být soubor **default.neon**
```neon
link: 'Homepage:'
position: 1

menu:
    group:
        test:
            link: ''
```

Presenter musí dědit z třídy **\NAttreid\Crm\Control\Presenter**
```php
namespace Package\Example\Presenters;

class TestPresenter extends \NAttreid\Crm\Control\Presenter {
    
}
```



