import { Image } from "@/types"
const Carousel = ({images} : {
    images: Image[]
}) => {
  return (
    <>
      <div className="flex items-start gap-8">
        <div className="flex flex-col items-center gap-2 py-2">
            {images.map((image, i) => (
                <a href={'#item' + i} className="border-2 hover:border-blue-500" key={image.id}>
                    <img src={image.thumb} alt={''} className="w-[50px]" />
                </a>
            ))}
        </div>
        {images.map((image, i ) => (
          <div id={'item' + i} key={image.id} className="carousel-item w-full">
            <img src={image.large} className="w-full" />
          </div>
        ))}
      </div>
    </>
  )
}

export default Carousel
