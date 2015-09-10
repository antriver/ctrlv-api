<a href="/"><img src="img/ctrlv-dark-358x142.png" alt="CtrlV.in" style="height:71px;" /></a>

<br/>

CtrlV now has a public API for uploading and manipulating images. You can use the <a href="#image-uploader">**Image Uploader**</a> to allow your website's users to upload images. Then you can use the **REST API** to manipulate those images as necessary.

<hr id="image-uploader"/>

# Image Uploader

To use the CtrlV uploader include our javascript file on your page:
```html
<script src="https://api.ctrlv.in/upload.js"></script>
```

Then call `ctrlv.upload()`:

```javascript
ctrlv.upload(function(image){
    // image is an object containing information about the uploaded image.
    console.log(image);
});
```

The contents of the `image` object are the same as those returned by <a href="#api-Images-GetImageId">Get An Image</a> below. The object contains an additional property named `key` that can be used to manipulate the image in future requests.

A demo <a href="http://ctrlvin.github.io/" target="_blank">can be seen here</a>.
