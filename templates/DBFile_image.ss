<% if not $IsWebP %>
    <picture>
        <source srcset="$WebP.URL" type="image/webp"/>
        <img $AttributesHTML />
    </picture>
<% else %>
    <img $AttributesHTML />
<% end_if %>
