import { Product } from "@/types"

export default function Show({
    product, variationOptions
} : {
    product: Product,
    variationOptions: number[]
}) {
    console.log(product, variationOptions)
  return (
    <div>
      test
    </div>
  )
}
