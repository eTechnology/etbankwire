{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if $status == 'ok'}
<div class="box">
	<h3>{l s='Your order on %s is COMPLETE.' sprintf=$shop_name mod='etbankwire'}</h3>
	<hr>
		<p>{l s='Gracias por realizar su pedido, puede realizar el depósito a nuestro numero de cuenta que se detalla a continuacion:' mod='etbankwire'}</p>
		<br>
		<div class="row">
			<div class="col-sm-6">
				<h4>{l s='Detalles de Beneficiario' mod='etbankwire'}</h4>
				<ul style="padding-left: 20px">
					<li><b> Beneficiario:​ </b> ​{$etbw_own_name} </li>
					<li><b> Nombre de Banco: </b> {$etbw_bank_name} </li>
					{if $etbw_own_acc_pen}
					<li><b> ​​Numero de Cuenta Soles: </b> {$etbw_own_acc_pen} </li>
					{/if}
					{if $etbw_own_acc_usd}
					<li><b> ​​Numero de Cuenta Dolares: </b> {$etbw_own_acc_usd} </li>
					{/if}
					{if $etbw_own_int_pen}
					<li><b> Codigo Interbancario Soles: </b> {$etbw_own_int_pen} </li>
					{/if}
					{if $etbw_own_int_usd}
					<li><b> Codigo Interbancario Dolares: </b> {$etbw_own_int_usd} </li>
					{/if}
					{if $etbw_bank_swift}
					<li><b> Codigo Swift: </b> {$etbw_bank_swift} </li>
					{/if}
					<li>{l s='Amount' mod='etbankwire'} <span class="price"><strong>{$total_to_pay}</strong></span></li>
				</ul>
			</div>
			<div class="col-sm-6">
				<h4>{l s='Detalles de banco' mod='etbankwire'}</h4>
				<ul style="padding-left: 20px">
					{if $etbw_bank_name}
					<li><b> Nombre de Banco: </b> {$etbw_bank_name} </li>
					{/if}
					{if $etbw_bank_address}
					<li><b> DIreccion: </b> {$etbw_bank_address} </li>
					{/if}
					{if $etbw_bank_phone}
					<li><b> Telfono: </b> {$etbw_bank_phone} </li>
					{/if}
					{if $etbw_bank_city}
					<li><b> ​Cliudad: </b> {$etbw_bank_city}</li>
					{/if}
					{if $etbw_bank_country}
					<li><b> Pais: </b> {$etbw_bank_country} </li>
					{/if}
				</ul>
			</div>
		</div>
	{if $etbw_txt_details} {$etbw_txt_details} {else} {l s='An email has been sent with this information.' mod='etbankwire'} {/if}
	<hr>
	<strong>{l s='Your order will be sent as soon as we receive payment.' mod='etbankwire'}</strong>
</div>

{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='etbankwire'} 
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='etbankwire'}</a>.
	</p>
{/if}