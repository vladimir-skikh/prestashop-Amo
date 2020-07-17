<div id="uipw-form_goods_modal">
    <div class="goods_info">
        <div class="goods_img">
            <img id="bigpic" itemprop="image" src=""/>
        </div>
        <div class="title"></div>
        <div class="price"><span class="current-price"></span> <sup class="discount"></sup></div>
	<p>
	</p>
	<p>
	       	<div>Товар будет зарезервирован за Вами, а информация о заказе передана оператору. Будет оформлена бесплатная доставка до ближайшего к вам пункта выдачи в вашем городе. Наш менеджер свяжется с Вами в течение часа, а если сейчас выходной или очень поздно, то в начале следующего рабочего дня (09:00-18:00 мск.).</div>
	</p>

    </div>
    <div class="goods_order">
        <form method="POST" action="{$order.link}" id="pworderform">
            <div class="title">{l s='Форма заказа' mod='pwoneclick'}</div>
            <div class="system_error"></div>
            <div class="uipw-modal_form_fields">
                <div>
                    <label for="goods_name">{l s='Имя' mod='pwoneclick'}<sup>*</sup></label>
                    <input name="firstname" id="goods_name" type="text" tabindex="1"/>
                    <div class="firstname_error"></div>
                </div>
                
				<div>
                    <label for="goods_phone">
						{l s='Телефон' mod='pwoneclick'}
						{if $config['phone']}
						<sup>*</sup>
						{/if}
					</label>
                    <input name="phone" id="goods_phone" type="tel" tabindex="2"/>
                    <div class="phone_error"></div>
                </div>
				{if $config['showemail']}
				<div>
                    <label for="goods_email">
						{l s='E-mail' mod='pwoneclick'}
						{if $config['email']}
						<sup>*</sup>
						{/if}</label>
                    <input name="email" id="goods_email" type="email" tabindex="3"/>
                    <div class="email_error"></div>
                </div>
				{/if}
                {if $config['city']}
				<div>
                    <label for="goods_city">
						{l s='Город' mod='pwoneclick'}
						{if $config['text']}
						<sup>*</sup>
						{/if}</label>
                    <input name="city" id="goods_city" type="text" tabindex="4"/ value="{$PW_COOCKIE_CITY}">
                    <div class="city_error"></div>
                </div>
				{/if}
				{if $config['comment']}
				<div>
                    <label for="goods_comment">
						{l s='Комментарий' mod='pwoneclick'}
					</label>
                    <textarea name="comment" id="goods_comment" type="comment" tabindex="5"/>
                    <div class="email_error"></div>
                </div>
				{/if}
                <input type="hidden" name="id_product" value=""/>
                <input type="submit" value="{l s='Заказать' mod='pwoneclick'} &rarr;" tabindex="6"/>
                <div class="pleace_wait alert alert-info">{l s='Идет оформление заказа, ожидайте...' mod='pwoneclick'}</div>
            </div>
        </form>
    </div>
    <section class="uipw-form_success alert alert-success"></section>
</div>
