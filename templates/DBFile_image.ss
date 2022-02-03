<% if not $IsWebP %>
    <picture>
        <source srcset="$WebP.Link" type="image/webp"/>
        <img $AttributesHTML />
    </picture>
<% else %>
    <img $AttributesHTML />
<% end_if %>
